class CtrUrl {

    constructor(rootpath = "") {
        this.global_root = rootpath;
        this.frontend = "";
        this.backend = "";
        this.func = "";
        this.cacheMap = new Map();
    }

    get(key){
        const params =  new URLSearchParams(window.location.search);
        return params.get(key) ?? null;
    }

}

const Url = new CtrUrl();

if (typeof module !== "undefined" && typeof module.exports !== "undefined") {
    module.exports = Url;
}
export default Url;