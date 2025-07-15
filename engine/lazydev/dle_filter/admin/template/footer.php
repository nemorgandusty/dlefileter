<?php
/**
* Дизайн админ панель
*
* @link https://lazydev.pro/
* @author LazyDev <email@lazydev.pro>
**/

if (!defined('DATALIFEENGINE') || !defined('LOGGED_IN')) {
	header('HTTP/1.1 403 Forbidden');
	header('Location: ../../');
	die('Hacking attempt!');
}

$jsAdminScript = implode($jsAdminScript);
$additionalJsAdminScript = implode($additionalJsAdminScript);
echo '
                        <div class="panel" style="margin-top: 20px;">
                            <div class="panel-content">
                                <div class="panel-body">
                                    &copy; <a href="https://lazydev.pro/" target="_blank">LazyDev</a> ' . date('Y', time()) . ' All rights reserved.
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <script src="engine/lazydev/' . $modLName . '/admin/template/assets/core.js"></script>
        <script>let coreAdmin = new Admin("' . $modLName . '"); ' . $jsAdminScript . '</script>
        <script>
let selectTag = tail.select(".selectTag", {
    search: true,
    multiSelectAll: true,
    classNames: "default white",
    multiContainer: true,
    multiShowCount: false,
    locale: "'. $_COOKIE['lang_dle_filter'] . '"
});
        </script>
        ' . $additionalJsAdminScript . '
    </body>
</html>';

?>