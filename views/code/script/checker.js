import twal from "../src/mods/twal.js";
import { Tyrax } from "../src/tyrux/main.js";

let data = new FormData();
data.append("web", "adsadas");
data.append("app", "adsadas");


Tyrax.ctrql({
    action: "insert",
    table: "customer",
    param: {user: "tyrone"},
    inspect: true,
    realtime: ["created_at", "updated_at"],
    response: (send)=>{
        twal.ok(send.value);
    }
});