"use strict";

export default class AjaxRequest {
    
	constructor(debug = false) {
		this.debug = debug;
    }

	makeAjaxCall(methodType, url, data, token) {
		return new Promise((resolve, reject) => {
            if (!token || token == '') {
                reject('CSRF token not provided.');
            }

            let xhr = new XMLHttpRequest();
            xhr.open(methodType, url, true);
            xhr.setRequestHeader('X-CSRF-TOKEN', token);
            if(this.debug) console.log(`x-csrf token set to: ${token}`);
			xhr.send(data);
			xhr.onreadystatechange = () => {
				if (xhr.readyState === 4) {
					if (xhr.status === 200) {
						if(this.debug) console.log('xhr done successfully');
						if(this.debug) {
							console.log('Ajax response:');
							console.log(xhr.responseText);
						}
						resolve(xhr);
					} else {
                        if(this.debug) console.log('xhr failed');
                        if(this.debug) console.log(xhr);
						reject(xhr.status);
					}
				} else {
					if(this.debug) console.log('xhr processing going on');
				}
			}
			if(this.debug) console.log('request sent successfully');
		});
	}
}