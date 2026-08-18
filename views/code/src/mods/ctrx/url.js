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

    append_params(newParams, isParam = true) {
        const params = new URLSearchParams(window.location.search);
    
        Object.entries(newParams).forEach(([key, value]) => {
            params.set(key, value);
        });
    
        if(isParam){
            return `?${params.toString()}`;
        }
        return params.toString();
    }
}

const Url = new CtrUrl();

if (typeof module !== "undefined" && typeof module.exports !== "undefined") {
    module.exports = Url;
}
export default Url;