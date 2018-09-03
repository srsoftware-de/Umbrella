<?php
include 'controller.php';
include '../bootstrap.php';

require_login('rtc'); 
warn('The RTC module is currently under development.');
warn('Most functions will not work at the moment.');

include '../common_templates/head.php';
include '../common_templates/main_menu.php'; 
include '../common_templates/messages.php'; ?>

<fieldset>
	<legend><?= t('WebRTC') ?></legend>
	<video id="localVideo" autoplay muted style="width:300px;"></video>
	<video id="remoteVideo" autoplay style="width:300px;"></video>
	<br />
	<input type="button" id="start" onclick="start(true)" value="Start Video"></input>
</fieldset>

<script src="https://webrtc.github.io/adapter/adapter-latest.js"></script>
<script type="text/javascript">

class Message {
	constructor() {
		this.servertime = null;
	}
	
	poll(){
		var msg = this;
		if (this.servertime == null){
			$.ajax({
				url: '<?= getUrl('rtc','server')?>',
				success: function(time) { msg.servertime = time; msg.poll(); }
			});
		} else {
			$.ajax({
				url: '<?= getUrl('rtc','server')?>',
				method: 'POST',
				data: { time: msg.servertime },
				success: function(data) {
					if (data == 'null') {
						setTimeout(function(){ msg.poll() },1000);
					} else {
						var json = JSON.parse(data);
						msg.servertime = json.time;
						var signal = JSON.parse(json.text);
						console.log(signal);
						msg.recieved(signal);
						
						setTimeout(function(){ msg.poll() },50);
					} 
 				}
			});
		}
	}

	recieved(signal){
		console.log('recieved signal:');
		console.log(signal);
	}

	send(json){
		$.ajax({
			async: false,
			url: '<?= getUrl('rtc','server')?>',
			method: 'POST',
			data: {
				message: JSON.stringify(json)
			},
		});
	}
}

var localVideo;
var localStream;
var remoteVideo;
var peerConnection;
var uuid;
var message = null;

var peerConnectionConfig = {
	'iceServers': [
		{'urls': 'stun:stun.stunprotocol.org:3478'},
		{'urls': 'stun:stun.l.google.com:19302'},
		{
		urls: 'turn:keawe.de:3478',
		username: 'srichter',
		credential: 'schwanzuslongus'
	},
	]
};

function createdDescription(description) {
	logtrace('got description');

	peerConnection.setLocalDescription(description).then(function() {
		logtrace('Sending loacl description');
		message.send({'sdp': peerConnection.localDescription, 'uuid': uuid});
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
	//remoteVideo.srcObject = stream;
}

function gotIceCandidate(event) {
	if(event.candidate != null) {
		logtrace('Sending ice candidate');
		message.send({'ice': event.candidate, 'uuid': uuid});
	}
}

function gotRemoteStream(event) {
	logtrace('got remote stream');
	console.log(event.streams);
	console.log(event.streams[0].getVideoTracks());
	remoteVideo.srcObject = event.streams[0];
}

function gotSignalFromServer(signal) {
	logtrace('Got signal from server: ');
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

	message = new Message(); // create message object and start polling
	message.recieved = gotSignalFromServer;
	message.poll();
	
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
	console.log(message.servertime+' - '+text);
}

pageReady();
</script>
<?php include '../common_templates/closure.php'; ?>