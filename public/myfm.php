<?php
/**
 * 🚀 FAST-DEV MANAGER v4.7 (Fixed: Removed exec() for Deletion)
 */

ini_set('display_errors', 1);
error_reporting(E_ALL);

$root_path = realpath(dirname(__FILE__) . '/../'); 
define('FM_ROOT_PATH', $root_path);
$current_p = isset($_GET['p']) ? trim($_GET['p'], '/') : '';
$full_path = FM_ROOT_PATH . ($current_p ? '/' . $current_p : '');

// --- RECURSIVE DELETE FUNCTION (Native PHP) ---
function delete_recursive($target) {
    if (is_dir($target)) {
        $files = array_diff(scandir($target), array('.', '..'));
        foreach ($files as $file) {
            delete_recursive($target . DIRECTORY_SEPARATOR . $file);
        }
        return rmdir($target);
    }
    return unlink($target);
}

// 1. DELETE LOGIC (Fixed Line 19 Error)
if (isset($_POST['delete_items']) && !empty($_POST['selected_files'])) {
    foreach ($_POST['selected_files'] as $item) {
        $target = $full_path . '/' . $item;
        if (file_exists($target)) {
            delete_recursive($target); // Calling native function instead of exec()
        }
    }
    header("Location: ?p=" . urlencode($current_p));
    exit;
}

// CREATE LOGIC
if (isset($_POST['create_item'])) {
    $target = $full_path . '/' . trim($_POST['name']);
    $_POST['type'] === 'file' ? file_put_contents($target, "") : mkdir($target, 0777, true);
    header("Location: ?p=" . urlencode($current_p)); exit;
}

// AJAX SAVE LOGIC
if (isset($_POST['ajax_save'])) {
    $file_to_save = FM_ROOT_PATH . '/' . $_POST['file_path'];
    if (is_file($file_to_save)) { 
        file_put_contents($file_to_save, $_POST['content']); echo "SUCCESS"; 
    } else { echo "ERROR"; }
    exit;
}

// GET & SORT
$raw_objects = (is_dir($full_path) && is_readable($full_path)) ? scandir($full_path) : [];
$folders = []; $files = [];
foreach ($raw_objects as $obj) {
    if ($obj == '.' || $obj == '..') continue;
    is_dir($full_path . '/' . $obj) ? $folders[] = $obj : $files[] = $obj;
}
natcasesort($folders); natcasesort($files);
$objects = array_merge($folders, $files);

echo '<meta name="viewport" content="width=device-width, initial-scale=1.0">';
echo '<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>';
?>

<style>
    :root { --h-purple: #a371f7; --h-dark: #0d1117; --h-bg: #161b22; --border: #30363d; --danger: #da3633; }
    body { background: var(--h-dark); color: #c9d1d9; font-family: sans-serif; margin:0; }
    .navbar { background: var(--h-bg); border-bottom: 1px solid var(--border); padding: 12px; display:flex; justify-content:space-between; align-items:center; position:sticky; top:0; z-index:10; }
    .navbar-brand { color: var(--h-purple); font-weight: bold; text-decoration:none; }
    
    #main-table { width: 100%; border-collapse: collapse; }
    #main-table td { padding: 12px; border-bottom: 1px solid var(--border); font-size: 14px; }
    
    .select-col { display:none; width:40px; text-align:center; }
    .btn-edit { background: #238636; color: #fff; padding: 5px 10px; border-radius: 6px; text-decoration:none; font-size: 11px; }

    #deleteBtn { position:fixed; bottom:25px; right:25px; background:var(--danger); color:white; border:none; padding:15px 25px; border-radius:50px; font-weight:bold; cursor:pointer; display:none; box-shadow: 0 4px 15px rgba(0,0,0,0.4); z-index:99; }

    .editor-overlay { position:fixed; top:0; left:0; width:100%; height:100%; background:var(--h-dark); z-index:1000; display:flex; flex-direction:column; }
    .editor-container { position: relative; flex: 1; display: flex; background: #010409; overflow:hidden; }
    .line-numbers { width: 45px; background: #0d1117; color: #484f58; font-family: monospace; font-size: 13px; text-align: right; padding: 15px 10px 15px 0; border-right: 1px solid var(--border); line-height: 1.5; overflow:hidden; }
    #codeEditor { flex: 1; background: transparent; color: #e6edf3; font-family: monospace; font-size: 13px; border: none; padding: 15px; resize: none; outline: none; line-height: 1.5; overflow:auto; }
</style>

<form id="mainForm" method="post">
    <div class="navbar">
        <a href="?p=" class="navbar-brand">🚀 SH DEV ROOT</a>
        <div>
            <span onclick="createItem('file')" style="cursor:pointer; margin-right:15px; color:#3fb950;">📄+</span>
            <span onclick="createItem('folder')" style="cursor:pointer; margin-right:15px; color:#d29922;">📁+</span>
            <span onclick="toggleSelect()" id="selToggle" style="cursor:pointer; font-size:18px;">☑️</span>
        </div>
    </div>

    <table id="main-table">
        <tbody>
            <?php foreach ($objects as $obj): 
                $is_dir = is_dir($full_path . '/' . $obj);
                $link = $is_dir ? "?p=" . trim($current_p . '/' . $obj, '/') : "#";
            ?>
            <tr>
                <td class="select-col">
                    <input type="checkbox" name="selected_files[]" value="<?php echo htmlspecialchars($obj); ?>" class="file-check">
                </td>
                <td onclick="<?php echo $is_dir ? "window.location='$link'" : "void(0)"; ?>">
                    <?php echo $is_dir ? '📁' : '📄'; ?> <?php echo htmlspecialchars($obj); ?>
                </td>
                <td style="text-align:right;">
                    <?php if(!$is_dir): ?>
                        <a href="?p=<?php echo $current_p; ?>&edit=<?php echo urlencode($obj); ?>" class="btn-edit">EDIT</a>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <button type="submit" name="delete_items" id="deleteBtn" onclick="return confirm('Pakka delete karna hai?')">
        🗑️ DELETE SELECTED (<span id="count">0</span>)
    </button>
</form>

<script>
function toggleSelect() {
    $('.select-col').toggle();
    if($('.select-col').is(':hidden')) {
        $('.file-check').prop('checked', false);
        $('#deleteBtn').hide();
    }
}

$('.file-check').on('change', function() {
    const checked = $('.file-check:checked').length;
    $('#count').text(checked);
    checked > 0 ? $('#deleteBtn').fadeIn() : $('#deleteBtn').fadeOut();
});

function createItem(type) {
    const name = prompt("Enter " + type + " name:");
    if(name) {
        $('<form method="post"><input type="hidden" name="create_item" value="1"><input type="hidden" name="type" value="'+type+'"><input type="hidden" name="name" value="'+name+'"></form>').appendTo('body').submit();
    }
}
</script>

<?php if (isset($_GET['edit'])): 
    $relative_file = ($current_p ? $current_p . '/' : '') . $_GET['edit'];
    $edit_file_path = FM_ROOT_PATH . '/' . $relative_file;
    $content = is_file($edit_file_path) ? file_get_contents($edit_file_path) : "";
?>
    <div class="editor-overlay">
        <div class="navbar">
            <span style="font-size:12px;"><?php echo htmlspecialchars($_GET['edit']); ?></span>
            <div>
                <button id="saveBtn" style="background:#238636; color:white; border:none; padding:6px 15px; border-radius:6px;">SAVE</button>
                <button onclick="window.history.back()" style="background:#30363d; color:white; border:none; padding:6px 15px; border-radius:6px; margin-left:5px;">CLOSE</button>
            </div>
        </div>
        <div class="editor-container">
            <div class="line-numbers" id="lineNumbers">1</div>
            <textarea id="codeEditor" spellcheck="false"><?php echo htmlspecialchars($content); ?></textarea>
        </div>
    </div>
    <script>
        const editor = document.getElementById('codeEditor');
        const lineNums = document.getElementById('lineNumbers');

        function updateLines() {
            const count = editor.value.split('\n').length;
            let html = '';
            for(let i=1; i<=count; i++) html += i + '<br>';
            lineNums.innerHTML = html;
        }

        editor.addEventListener('input', updateLines);
        editor.addEventListener('scroll', () => { lineNums.scrollTop = editor.scrollTop; });
        updateLines();

        $('#saveBtn').on('click', function() {
            $(this).text('...');
            $.post('', { ajax_save:1, file_path:'<?php echo addslashes($relative_file); ?>', content: $('#codeEditor').val() }, (res) => {
                $(this).text(res === "SUCCESS" ? 'SAVED ✅' : 'ERR');
                setTimeout(() => $(this).text('SAVE'), 1000);
            });
        });
    </script>
<?php endif; ?>
