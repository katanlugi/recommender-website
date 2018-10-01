<template>
    <div class="panel panel-default">
        <div class="panel-heading">
            <h3 class="panel-title">Recommender Server</h3>
        </div>
        <div class="panel-body">
            <ul class="list-group">
                <li class="list-group-item">
                    <label for="server" class="control-label col-md-8">Recommendation server status</label>
                    <label class="label label-danger" v-if="!isServerRunning">Stopped</label>
                    <label class="label label-success" v-if="isServerRunning">Running</label>
                </li>
                <li class="list-group-item">
                  <label for="toggle-recom-server"
                        class="control-label col-md-8">
                    Start/Stop Recommendation Server
                  </label>
                  <button type="button"
                      name="toggle-recom-server"
                      @click="toggleRecomServer"
                      class="btn btn-warning">
                    {{this.toggleServerText}}
                  </button>
                </li>
                <li class="list-group-item">
                    <label for="precompute" class="control-label col-md-8">Precompute model</label>
                    <button type="button" 
                        name="precompute"
                        class="btn btn-default"
                        @click="precompute"
                        :disabled="!isServerRunning">{{ precomputeText }}
                    </button>
                </li>
            </ul>
        </div>
        <div class="panel-footer">
            
        </div>
        </div>
</template>
<script>
const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
export default {
    data() {
        return {
            isServerRunning: false,
            precomputeText: 'Load / Precompute',
            precomputeStatus: false,
            toggleServerText: 'Start', 
        }
    },
    mounted() {
        this.updateServerStatus();
    },
    methods: {
        updateServerStatus() {
            console.log('checking server status...');
            
            fetch('/recomServerStatus', {
              method: 'POST',
              credentials: 'same-origin',
              headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': token,
              },
            }).then((response) => {
              console.log('server status received...');
              console.log(response);
              response.json().then(({serverRunning}) => {
                console.warn(serverRunning);
                this.isServerRunning = serverRunning;
                if (serverRunning) {
                  this.toggleServerText = 'Stop';
                } else {
                  this.toggleServerText = 'Start';
                }
              });
            }).catch((error) => console.error(error));
        },
        toggleRecomServer() {
          if (this.isServerRunning) {
            this.stopRecomServer();
          } else {
            this.startRecomServer();
          }
        },
        startRecomServer() {
            this.toggleServerText = 'Starting Server...';
            fetch('/startRecomServer', {
              method: 'POST',
              credentials: 'same-origin',
              headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': token,
              },
            }).then((response) => {
              this.toggleServerText = 'Server Started';
              setTimeout(() => {
                this.updateServerStatus()
              }, 1000);
            }).catch((error) => {
              this.toggleServerText = 'Something went wrong...';
              this.$nextTick(() => {
                this.updateServerStatus();;
              });
            });
        },
        stopRecomServer() {
            const url = 'stopRecomServer';
            this.toggleServerText = 'Stopping Server...';
            ajaxRequest.makeAjaxCall(
                'post', url, null, token
            ).then(rsp => {
                this.toggleServerText = 'Stopped';
                setTimeout(() => {
                  this.updateServerStatus()
                }, 500);
            }).catch(err => console.error(err));
        },
        precompute() {
            const url = 'precompute';
            this.precomputeText = 'Precomputing...';
            ajaxRequest.makeAjaxCall(
                'post', url, null, token
            ).then(rsp => {
                this.precomputeText = 'Model precomputed';
                this.precomputeStatus = true;
            }).catch(err => console.error(err));
        }
    }
}
</script>
