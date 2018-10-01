<template>
  <div>
    <div v-if="!isReady"
        class="col-md-12">
      <div class="page-header-container">
        <div>
          <h2>{{ this.statusText }}</h2>
        </div>
      </div>
    </div>
    <movies-grid 
      v-else
      :title="title"
      :movies="movies"
      :paginated="false">
    </movies-grid>
  </div>
</template>

<script>
const _token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
export default {
  props: ['title', 'url'],
  data() {
    return {
      isReady: false,
      movies: Array,
      statusText: 'Processing request, please wait...',
      userId: null,
    }
  },
  created() {
    this.getUserId();
    this.generateRecommendations();
  },
  methods: {
    getUserId() {
      fetch('/json/userid', {
        method: 'GET',
        credentials: 'same-origin',
        headers: {
          'Accept': 'application/json',
          'X-CSRF-TOKEN': _token,
        },
      }).then((response) => {
        response.json().then((data) => {
          this.userId = data;
        });
      });
    },
    updateStatusText(position, nbJobs) {
      switch (position) {
        case -1:
        case undefined:
          this.statusText = `You are in a queue of ${nbJobs} jobs`;
          break;
        case 0:
          this.statusText = 'Computing your recommendations... Please wait';
          break;
        default:
          this.statusText = `You are in position ${position} out of ${nbJobs}. Please wait...`;
          break;
      }
      // if (position === 0) {
      //   this.statusText = 'Computing your recommendations... Please wait';
      // } else {
      //   this.statusText = `You are in position ${position} out of ${nbJobs}. Please wait...`;
      // }
    },
    generateRecommendations() {
      fetch(this.url, {
        method: 'GET',
        credentials: 'same-origin',
      }).then((response) => {
        response.json().then(({status, nbJobs, position}) => {
          if (status === 'pending') {
            this.updateStatusText(position, nbJobs);
          }
        });
        this.checkForRecommendations();
      });
    },
    checkForRecommendations() {
      const ms = 2000;
      const self = this;
      let check = function() {
        fetch('/json/has-recommendations', {
          method: 'GET',
          credentials: 'same-origin',
          headers: {
            'Accept': 'application/json',
            'X-CSRF-TOKEN': _token,
          },
        }).then((response) => {
          console.log(`/json/hasRecommendation response code: ${response.status}`);
          response.json().then((data) => {
            if (data.status === 'ok') {
              clearInterval(interval);
              self.retrieveRecommendations();
            } else {
              self._parseForPositionInQueue(data.jobs);
            }
          });
        }).catch((error) => {
          console.error(error);
        });
      };
      let interval = setInterval(check, ms);
    },
    _parseForPositionInQueue(jobs) {
      if (!this.userId) {
        this.updateStatusText(-1, jobs.length);
        return;
      }
      for (const [i, value] of jobs.entries()) {
        const val = JSON.parse(value);
        if (val.displayName === 'App\\Jobs\\RecommendMovies') {
          const parts = val.data.command.split(';')[1].split(':');
          if (parts[0] === 'i' && parts[1] === `${this.userId}`) {
            this.updateStatusText(i, jobs.length);
            return;
          }
        }
      }
    },
    retrieveRecommendations() {
      console.log('retrieving recommendations...');
      fetch('/json/get-recommendations', {
        method: 'GET',
        credentials: 'same-origin',
        headers: {
          'Accept': 'application/json',
          'X-CSRF-TOKEN': _token,
        },
      }).then((response) => {
        console.log(response);
        response.json().then((data) => {
          console.log(data);
          this.movies = data;
          this.isReady = true;
        })
      }).catch((err) => {
        console.error(err);
      });
    },
  },
}
</script>

<style>

</style>
