"use strict";

export default class Loading {
    constructor(debug = false) {
        console.log('loading -> constructor');
        this.debug = debug;
        
        this.loadingHTML = `
            <div class="full-screen">
                <div class="preloader-1">
                    <div>Loading</div>
                    <span class="line line-1"></span>
                    <span class="line line-2"></span>
                    <span class="line line-3"></span>
                    <span class="line line-4"></span>
                    <span class="line line-5"></span>
                    <span class="line line-6"></span>
                    <span class="line line-7"></span>
                    <span class="line line-8"></span>
                    <span class="line line-9"></span>
                </div>
            </div>
        `;
    }

    displayLoading(el) {
        console.log('loading -> display');
        console.log(el);
        el.innerHTML = this.loadingHTML;
        console.log(el);
    }

    hideLoading(el) {
        console.log('loading -> hide');
        el.innerHTML = '';
        //el.removeChild(this.div);
    }
}