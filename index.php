<?php
include 'controller.php';
include '../bootstrap.php';

require_login('rtc'); 

include '../common_templates/head.php';
include '../common_templates/main_menu.php'; ?> 

<fieldset>
	<legend><?= t('WebRTC') ?></legend>
	<video id="localVideo" autoplay muted style="width:300px;"></video>
	<video id="remoteVideo" autoplay style="width:300px;"></video>
	<br />
	<input type="button" id="start" onclick="start(true)" value="Start Video"></input>
</fieldset>

<script src="https://webrtc.github.io/adapter/adapter-latest.js"></script>
<script type="text/javascript">
var localVideo;
var localStream;
var remoteVideo;
var peerConnection;
var uuid;
var servertime = null;
var waiting = false;

var peerConnectionConfig = {
	'iceServers': [
		{'urls': 'stun:stun.stunprotocol.org:3478'},
		{'urls': 'stun:stun.l.google.com:19302'},
	]
};

function createdDescription(description) {
	logtrace('got description');

	peerConnection.setLocalDescription(description).then(function() {
		logtrace('Sending loacl description');
		postMessage({'sdp': peerConnection.localDescription, 'uuid': uuid});
	}).catch(errorHandler);
}

//Taken from http://stackoverflow.com/a/105074/515584
//Strictly speaking, it's not a real UUID, but it gets the job done here
function createUUID() {
	function s4() {
		return Math.floor((1 + Math.random()) * 0x10000).toString(16).substring(1);
	}

	return s4() + s4() + '-' + s4() + '-' + s4() + '-' + s4() + '-' + s4() + s4() + s4();
}

function errorHandler(error) {
	logtrace(error);
}

function getUserMediaSuccess(stream) {
	localStream = stream;
	localVideo.srcObject = stream;
}

function gotIceCandidate(event) {
	if(event.candidate != null) {
		logtrace('Sending ice candidate');
		postMessage({'ice': event.candidate, 'uuid': uuid});
	}
}

function gotRemoteStream(event) {
	logtrace('got remote stream');
	console.log(event.streams);
	remoteVideo.srcObject = event.streams[0];
}

function gotSignalFromServer(signal) {
	logtrace('Signal: ');
	console.log(signal);
	if(!peerConnection) start(false);

	// Ignore messages from ourself
	if(signal.uuid == uuid) return;

	if(signal.sdp) {
		peerConnection.setRemoteDescription(new RTCSessionDescription(signal.sdp)).then(function() {
			// Only create answers in response to offers
			if(signal.sdp.type == 'offer') {
				peerConnection.createAnswer().then(createdDescription).catch(errorHandler);
			}
		}).catch(errorHandler);
	} else if(signal.ice) {
		peerConnection.addIceCandidate(new RTCIceCandidate(signal.ice)).catch(errorHandler);
	}
}

function messageRecieved(data){
	servertime = data.time;
	logtrace('recieved message from '+data.ip);
	
	signal = JSON.parse(data.text);
	gotSignalFromServer(signal);
}

function pageReady() {
	uuid = createUUID();

	localVideo = document.getElementById('localVideo');
	remoteVideo = document.getElementById('remoteVideo');

	var constraints = {
		video: true,
		audio: true,
	};

	if(navigator.mediaDevices.getUserMedia) {
		navigator.mediaDevices.getUserMedia(constraints).then(getUserMediaSuccess).catch(errorHandler);
	} else {
		alert('Your browser does not support getUserMedia API');
	}

	poll();
}

function poll(){
	if (servertime == null){
		$.ajax({
			url: '<?= getUrl('rtc','server')?>',
			success: function(data){
				logtrace('Got server time: '+data);
				servertime = data;
				poll();
			}
		});
	} else {
		$.ajax({
			url: '<?= getUrl('rtc','server')?>',
			method: 'POST',
			data: {
				time: servertime
			},
			success: function(data){
				
				if (data == 'null') {
					setTimeout(poll,1000);
				} else {
					setTimeout(poll,50);
					messageRecieved(JSON.parse(data));
}
			}
		});
	}
}

function postMessage(json){
	$.ajax({
		async: false,
		url: '<?= getUrl('rtc','server')?>',
		method: 'POST',
		data: {
			message: JSON.stringify(json)
		},
		success: function(data){
			servertime = data;
		}
	});
}

function start(isCaller) {
	peerConnection = new RTCPeerConnection(peerConnectionConfig);
	//peerConnection = new RTCPeerConnection();
	peerConnection.onicecandidate = gotIceCandidate;
	peerConnection.ontrack = gotRemoteStream;
	peerConnection.addStream(localStream);

	if(isCaller) {
		peerConnection.createOffer().then(createdDescription).catch(errorHandler);
	}
}

function logtrace(text){
	console.log(servertime+' - '+text);
}

pageReady();
</script>
<?php include '../common_templates/closure.php'; ?>