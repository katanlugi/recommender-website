
/**
 * First we will load all of this project's JavaScript dependencies which
 * includes Vue and other libraries. It is a great starting point when
 * building robust, powerful web applications using Vue and Laravel.
 */

require('./bootstrap');

window.Vue = require('vue');

/**
 * Next, we will create a fresh Vue application instance and attach it to
 * the page. Then, you may begin adding components to this application
 * or customize the JavaScript scaffolding to fit your unique needs.
 */
import AjaxRequest from './AjaxRequest.js';
import Loading from './Loading.js';
window.ajaxRequest = new AjaxRequest(false);
window.loading = new Loading(false);

const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

Vue.component('movies_search', {
  template: '#movies-search-template',
  props: ['list'],
  created() {
    const url = `/startsWith/${str}/7`;
    console.log(`starting request to ${url}`);
    ajaxRequest.makeAjaxCall('post',url,null,token).then(rsp => {
        console.log('received answer');
    }).catch(err => console.error(err));
  }
});

Vue.component(
  'movies-grid',
  require('./components/MoviesGrid.vue')
);

Vue.component(
  'recommendations',
  require('./components/Recommendations.vue')
);

Vue.component(
  'loading',
  require('./components/Loading.vue')
);

Vue.component(
  'my-rating',
  require('./components/MyRating.vue')
);

Vue.component(
  'admin-recom-server',
  require('./components/AdminRecomServer.vue')
);

new Vue({
  el: '#app',
  data: {
      list: ''
  }
});
