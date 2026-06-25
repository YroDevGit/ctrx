/**
 * This is tyrax config
 */

import Ctr from "../src/mods/ctr.js";
import Loading from "../src/mods/loading.js";

//This is tyrax default header
export const headerHandler = {
    Authorization: "Bearer sometoken",
    "Content-Type": "application/json",
}

//Tyrax loading
export const CtrLoading = {
    wait: ()=> Loading.load(true),
    done: ()=> Loading.load(false)
}

/**
 * Error Handler
 * This is tyrax error handler, where catches all http errors
 */
export const errorHandler = (error, message) => {
    /**
     * Default is alert(), you can change it.
     */
    alert(message);
    if(error.code == 707){
        Ctr.redirect("logout");
        return;
    }

}


