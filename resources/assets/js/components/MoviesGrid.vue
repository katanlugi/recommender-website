<template>
  <div class="col-md-12">
      <div class="page-header-container">
          <div>
              <h2>{{ title }}</h2>
          </div>
      </div>
      <div class="movies-container" ref="test">
          <loading v-if="this.isLoading">
              <div slot="title">{{ this.loadingText }}</div>
              <div slot="description">[This may take 30 sec or more...]</div>
          </loading>
          <movie-card v-if="isPaginated"
              v-for="movie in moviesResource.data" 
              :key="movie.id" :movie="movie"></movie-card>
          <movie-card v-if="!isPaginated"
              v-for="movie in moviesResource" 
              :key="movie.id" :movie="movie"></movie-card>
      </div>
      <pagination :data="moviesResource"
          v-on:pagination-change-page="fetchMovies"
          :limit="3"
          v-if="isPaginated"></pagination>
  </div>
</template>

<script>
  import MovieCard from './MovieCard.vue';
  import Loading from './Loading.vue';

  Vue.component('pagination', require('laravel-vue-pagination'));

  export default {
      props: ['title', 'url', 'movies', 'paginated'],
      components: {
          MovieCard, Loading
      },
      data() {
          return {
              isLoading: true,
              loadingText: 'Loading...',
              moviesResource: {},
              pagination: {
                  page: 1,
                  previous: false,
                  next: true
              }
          }
      },
      mounted() {
          this.isLoading = true;
          this.loadingText = 'Initializing connections...';
      },
      created() {
          // this.fetchMovies();
          this.loadMovies();
          console.log(`url: ${this.url}`);
      },
      methods: {
        loadMovies() {
          if (this.url) {
            this.fetchMovies();
          } else if(this.movies) {
            this.$nextTick(() => {
              this.loadingText = 'Done!';
              this.isLoading = false;
            });
            this.moviesResource = this.movies;
          }
        },
        fetchMovies(page) {
          this.$nextTick(() => {
            this.loadingText = 'Loading movies';
          });
          if (typeof page === 'undefined') {
            page = 1;
          }
          
          
          let u = this.url;
          if (this.isPaginated){
            u += `?page=${page}`;
          }
          console.log(`fetching ${u}`);
          fetch(u, {
            method: 'get',
            credentials: 'same-origin'
          }).then(response => {
            if (response.status !== 200) {
              console.warn(`Looks like there was a problem. Status Code: ${response.status}`);
              return;
            }
            console.log(response);
            response.json().then(rsp => {
              console.log(rsp);
              this.moviesResource = rsp;
              this.loadingText = 'done!';
              this.isLoading = false;
            });
          }).catch(e => console.error(e));
        }
      },
      computed: {
          isPaginated() {
              return this.paginated === 'true' || this.paginated;
          }
      }
  }
</script>
