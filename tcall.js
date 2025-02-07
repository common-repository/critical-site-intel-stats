var xmlHttp;

function tcall(s) { 
	xmlHttp = GetXmlHttpObject();
	if (xmlHttp == null) {
		alert ("Browser does not support HTTP Request");
		return;
	}
	xmlHttp.onreadystatechange = stateChanged;
	xmlHttp.open("GET",s,true);
	xmlHttp.send(null);
}

function stateChanged() { 
	if (xmlHttp.readyState == 4 || xmlHttp.readyState == "complete") { 
	} 
}

function GetXmlHttpObject() {
	var xmlHttp = null;
	try {
		// Firefox, Opera 8.0+, Safari
		xmlHttp = new XMLHttpRequest();
	}
	catch (e) {
		// Internet Explorer
		try {
			xmlHttp = new ActiveXObject("Msxml2.XMLHTTP");
		}
		catch (e) {
			xmlHttp = new ActiveXObject("Microsoft.XMLHTTP");
		}
	}
	return xmlHttp;
}