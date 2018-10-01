<template>
    <div>
        <div class="full-screen" v-bind:style="styleObject">
            <div class="preloader-1">
                <slot name="title" class="title">Loading</slot>
                <div class="lines">
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
                <slot name="description" class="description">[This may take some time...]</slot>
            </div>
        </div>
    </div>
</template>
<script>
export default {
    data() {
        return {
            styleObject: {
                backgroundColor: 'black',
                width: '0px'
            }
        }
    },
    mounted() {
        // give component the same width than the parent
        this.$nextTick(() => {
            const parentWidth = this.$parent.$refs.test.clientWidth;
            this.styleObject.width = `${parentWidth}px`;
        })
    }
}
</script>

<style scoped>
.full-screen {
    background: #000;
    height: 120px;
}
.preloader-1 {
    margin: 0px auto 0;
    width: 100%;
    /* height: 15px; */
}
.preloader-1 div {
    color: #fff;
    margin: 5px 0;
    text-transform: uppercase;
    text-align: center;
    font-family: 'Arial', sans-serif;
    font-size: 15px;
    letter-spacing: 2px;
}

.title {
    padding-top: 10px;
}

.preloader-1 .line {
    width: 1px;
    height: 12px;
    background: #fff;
    margin: 0 1px;
    display: inline-block;
    animation: opacity-1 1000ms infinite ease-in-out;
}

.preloader-1 .line-1 { animation-delay: 800ms; }
.preloader-1 .line-2 { animation-delay: 600ms; }
.preloader-1 .line-3 { animation-delay: 400ms; }
.preloader-1 .line-4 { animation-delay: 200ms; }
.preloader-1 .line-6 { animation-delay: 200ms; }
.preloader-1 .line-7 { animation-delay: 400ms; }
.preloader-1 .line-8 { animation-delay: 600ms; }
.preloader-1 .line-9 { animation-delay: 800ms; }

@keyframes opacity-1 { 
    0% { 
        opacity: 1;
    }
    50% { 
        opacity: 0;
    }
    100% { 
        opacity: 1;
    }  
}
</style>
