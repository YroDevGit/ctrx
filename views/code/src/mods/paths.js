class CtrPath {
    constructor(rootpath = "http://localhost:9999") {
        this.global_root = rootpath;
        this.frontend = "?page=";
        this.backend = "?be=";
        this.func = "?funcpage=";
    }

    page($page = "", params = {}) {
        if(! $page || $page == "/"){
            return "/";
        }
        let url = this.frontend + $page;
        if (typeof params === "object" && Object.keys(params).length > 0) {
            const query = Object.entries(params)
                .map(([k, v]) => `${encodeURIComponent(k)}=${encodeURIComponent(v)}`)
                .join("&");
            url += "?" + query;
        }
        return url;
    }

    funcpage($page = "", params = {}) {
        let url = this.func + $page;
        if (typeof params === "object" && Object.keys(params).length > 0) {
            const query = Object.entries(params)
                .map(([k, v]) => `${encodeURIComponent(k)}=${encodeURIComponent(v)}`)
                .join("&");
            url += (url.includes("?") ? "&" : "&") + query;
        }
        return url;
    }

    backend($be = "", params = {}) {
        let url = this.backend + $be;
        if (typeof params === "object" && Object.keys(params).length > 0) {
            const query = Object.entries(params)
                .map(([k, v]) => `${encodeURIComponent(k)}=${encodeURIComponent(v)}`)
                .join("&");
            url += (url.includes("?") ? "&" : "&") + query;
        }
        return url;
    }

    assets(file){
        return `_frontend/assets/${file}`;
    }
}

const PATH = new CtrPath();
const Path = PATH;

if (typeof window !== "undefined") {
    window.PATH = PATH;
    window.PATHS = PATH;
    window.Path = PATH;
}

if (typeof module !== "undefined" && typeof module.exports !== "undefined") {
    module.exports = PATH;
    module.exports = Path;
}

export default Path;