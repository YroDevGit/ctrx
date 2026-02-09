class SOUND {
    constructor(path) {
        this.basePath = "_frontend/assets/";
        const fullpath = this.basePath + path;
        this.audio = new Audio(fullpath);
        this.audio.preload = "auto";
    }

    play() {
        return this.audio.play().catch(err => {
            console.error("Playback failed:", err);
        });
    }

    pause() {
        this.audio.pause();
    }

    duration() {
        return this.audio.duration;
    }

    get_duration(callback) {
        if (this.audio.readyState > 0) {
            callback(this.audio.duration);
        } else {
            this.audio.addEventListener("loadedmetadata", () => {
                callback(this.audio.duration);
            }, { once: true });
        }
    }

    current_time() {
        return this.audio.currentTime;
    }

    stop() {
        this.audio.pause();
        this.audio.currentTime = 0;
    }

    set_volume(v) {
        this.audio.volume = Math.max(0, Math.min(1, v));
    }

    add_volume(){
        if(this.audio.volume >= 1){
            return;
        }
        this.audio.volume = this.audio.volume + 0.1;
        console.log(this.audio.volume);
    }

    minus_volume(){
        if(this.audio.volume <= 0){
            return;
        }
        this.audio.volume = this.audio.volume - 0.1;
        console.log(this.audio.volume);
    }

    get_volume(){
        return this.audio.volume;
    }

    loop(enable = true) {
        this.audio.loop = enable;
    }

    actions(options) {
        if (options.play) {
            const p = options.play;
            document.querySelector(p).addEventListener("click", () => {
                this.play();
            });
        }
        if (options.pause) {
            const p = options.pause;
            document.querySelector(p).addEventListener("click", () => {
                this.pause();
            });
        }
        if (options.stop) {
            const p = options.stop;
            document.querySelector(p).addEventListener("click", () => {
                this.stop();
            });
        }
    }
}

const Sound = SOUND;
export default Sound;