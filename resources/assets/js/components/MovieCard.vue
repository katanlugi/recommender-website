<template>
    <div class="movie-item">
        <div class="movie">
            <a :href="`/movies/${movie.id}`">
                <img class="movie-img" :src="imagePath" @error="imageNotFound()" @load="loaded"/>
            </a>
            <div class="text-movie-cont">
                <div class="mr-grid">
                    <div class="col1">
                        <h1>{{ movie.title }}</h1>
                        <ul class="movie-gen">
                            <li>{{ movie.runtime }} min /</li>
                            <li>{{ movie.release_date }} /</li>
                            <li>{{ movie.imdb_id }}</li>
                            <li>Average rating: <!--{{ round($movie->averageRating(), 2) }} --></li>
                        </ul>
                    </div>
                </div>
                <div class="mr-grid summary-row">
                    <h5>{{ movie.tagline }}</h5>
                    <div class="col2>">
                        <my-rating :rating-default="this.movie.rating" :movie-id="this.movie.id"></my-rating>
                    </div>
                </div>
                <div class="mr-grid action-row">
                    <div class="col2">
                        <div class="watch-btn">
                            <h3>
                                <a :href="`/movies/${movie.id}`"><i class="glyphicon glyphicon-play"></i>MORE</a>
                            </h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
const _token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
// import StarRating from 'vue-star-rating';
import MyRating from './MyRating.vue';

export default {
  components: {
      MyRating
  },
  props:['movie'],
  data() {
      return {
          imagePath: `/images/movies/${this.movie.id}.jpg`,
          retryCounter: 0,
      }
  },
  methods: {
    imageNotFound(ref) {
      console.warn(`image not found ${this.movie.id}`);
      this.$nextTick(() => {
        this.imagePath = `/images/film-Icon.png`;
        this.triggerLazyRetrieveOfImage();
      });
    },
    triggerLazyRetrieveOfImage() {
      console.log('lazy retrieve movie image from MovieDB');
      if (this.retryCounter >= 1) {
        // make sure that we don't end up into an infinit request loop.
        return
      }
      this.retryCounter += 1;
      fetch(`/poster/${this.movie.id}`, {
        method: 'get',
        credentials: 'same-origin'
      }).then((response) => {
        if (response.status !== 200) {
          console.warn(`Oops something's wrong! Status code: ${response.status}`);
          return;
        }
        console.log(response);
        this.$nextTick(() => {
          this.imagePath = `/images/movies/${this.movie.id}.jpg`;
        })
      }).catch(e => console.error(e));
    },
    loaded() {
      console.log('image loaded...');
      
    }
  }
}
</script>