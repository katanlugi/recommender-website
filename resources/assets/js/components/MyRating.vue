<template>
    <div class="movie-like">
        <a @click="resetRating" v-if="this.rating">
            <span class="glyphicon glyphicon-remove-circle"/>
        </a>

        <star-rating :increment="0.5"
            :border-width="1"
            :star-size="20"
            :show-rating="false"
            @rating-selected="setRating"
            v-model="rating">
        </star-rating>
    </div>
</template>
<style scoped>
.movie-like > a {
    float: left;
    margin: 1px;
    font-size: 1.3em;
    /* display: inline-block; */
    text-decoration: none;
    color: red;
}
</style>
<script>
import StarRating from 'vue-star-rating';
const _token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
export default {
    components: {
        StarRating
    },
    props: ['ratingDefault', 'movieId'],
    data() {
        return {
            rating: null
        }
    },
    mounted() {
        this.rating = this.ratingDefault;
    },
    methods: {
        setRating() {
            console.log(`Sending rating ${this.rating} for movieId ${this.movieId}`);
            
            let formData = new FormData();
            formData.append('movie_id', this.movieId);
            formData.append('rating', this.rating);
            
            fetch('/ratings', {
                method: 'post',
                credentials: 'same-origin',
                headers: {
                  'Accept': 'application/json',
                  'X-CSRF-TOKEN': _token,
                },
                body: formData
            }).then(response => {
                if (response.status !== 200) {
                    console.warn(`Looks like there was a problem. Status Code: ${response.status}`);
                    return false;
                }
                return true;
            }).catch(e => console.error(e));
        },
        resetRating() {
            this.rating = 0;
            fetch(`/ratings/delete/${this.movieId}`, {
              method: 'post',
              credentials: 'same-origin',
              headers: {
                'Accept': 'Application/json',
                'X-CSRF-TOKEN': _token,
              },
            }).then((response) => {
              console.log(response)
            }).catch((err) => console.error(err));
        }
    }
}
</script>
