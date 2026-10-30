/**
 * This is tyrax config
 */

import $$ from "../src/mods/ctrx/ctrx.js";
import Loading from "../src/mods/loading.js";

//This is tyrax default header
export const headerHandler = {
    Authorization: "Bearer sometoken",
    "Content-Type": "application/json",
    "timezone": $$.timezone
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
        $$.redirect("logout");
        return;
    }

}