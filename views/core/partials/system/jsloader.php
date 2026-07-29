<?php
if(! function_exists("private_loadAllJsFiles")){
    function private_loadAllJsFiles(){
        $glob = $GLOBALS['ctrx_js_includes_1993664_yro'];
        if (is_array($glob)) {
            $jsnencdctrx = json_encode($glob);
            ?>
            <script id="scriptloader_ctrx_yro_loader199363">
                window.addEventListener("load", async () => {
                    const alljjsnencdctrx = `<?= $jsnencdctrx ?>`;
                    const alljsnParsed = JSON.parse(alljjsnencdctrx);
    
                    const promises = Object.values(alljsnParsed).map(src => {
                        return new Promise((resolve, reject) => {
                            const scr = document.createElement("script");
                            scr.src = src;
                            scr.type = "module";
                            scr.onload = resolve;
                            scr.onerror = reject;
                            document.head.appendChild(scr);
                        });
                    });
                    await Promise.all(promises);
                    document.querySelector("#scriptloader_ctrx_yro_loader199363")?.remove();
                });
            </script>
            <?php
        }
    }
}