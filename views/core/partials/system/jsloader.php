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
                    document.querySelector("#scriptloader_ctrx_yro_loader199363")?.remove();
                });
            </script>
            <?php
        }
    }
}