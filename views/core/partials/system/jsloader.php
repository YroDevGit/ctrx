<?php
if (! function_exists("private_loadAllJsFiles")) {
    function private_loadAllJsFiles()
    {
        if (! isset($GLOBALS['ctrx_js_includes_1993664_yro2']) && fe_config("js_autoload") == true) {
            js(true);
        }
        $glob = $GLOBALS['ctrx_js_includes_1993664_yro'] ?? [];
        if (is_array($glob)) {
            $jsnewctr1 = json_encode($GLOBALS['ctrx_js_includes_1993664_yro1'] ?? []);
            $jsnencdctrx = json_encode($glob);
            ?>
            <div id="ctrx-loading-overlay-blourer" style="position:fixed;top:0;left:0;width:100%;height:100%;backdrop-filter:blur(10px);-webkit-backdrop-filter:blur(10px);background:rgba(255,255,255,0.05);z-index:9999;display:flex;justify-content:center;align-items:center;transition:opacity 0.8s ease-out;">
            </div>
            <style id="ctrx-loader-style-blourer">
                @keyframes ctrx-spin {
                    0% { transform: rotate(0deg); }
                    100% { transform: rotate(360deg); }
                }
                #ctrx-loading-overlay-blourer.hidden {
                    opacity: 0;
                }
            </style>
            
            <script id="scriptloader_ctrx_yro_loader199363">
                window.addEventListener("load", async () => {
                    let errsYros121 = `<?= $jsnewctr1 ?>`;
                    let newYrosErrs = JSON.parse(errsYros121);
                    if (Object.keys(newYrosErrs).length > 0) {
                        console.log(
                            "%cCTRX: %cjs extensions not found.!",
                            "color: ##1b3a6b; font-size: 12px; font-weight: bold;background-color: #a0ffb9; padding: 5px 2px;",
                            "color: #ff4444; font-size: 12px; background-color: #a0ffb9;padding: 5px 2px;",
                            newYrosErrs
                        );
                    }

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
                    
                    const overlay = document.querySelector("#ctrx-loading-overlay-blourer");
                    if (overlay) {
                        overlay.classList.add("hidden");
                        setTimeout(() => {
                            overlay.remove();
                            document.querySelector("#ctrx-loader-style-blourer")?.remove();
                        }, 800);
                    }
                    
                    document.querySelector("#scriptloader_ctrx_yro_loader199363")?.remove();
                });
            </script>
            <?php
        }
    }
}