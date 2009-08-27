
<?
function boposts_request($name, $default=null)
{
    if (!isset($_REQUEST[$name])) return $default;
    if (get_magic_quotes_gpc()) return boposts_stripslashes($_REQUEST[$name]);
    else return $_REQUEST[$name];
}

function boposts_stripslashes($value)
{
    $value = is_array($value) ? array_map('boposts_stripslashes', $value) : stripslashes($value);
    return $value;
}

if (isset($_POST['defaults']))
{
    @include(dirname(__FILE__) . '/en_US_options.php');
    if (WPLANG != '') @include(dirname(__FILE__) . '/' . WPLANG . '_options.php');

    update_option('boposts', $boposts_options);
}

if (isset($_POST['save']))
{
    $options = boposts_request('options');
    update_option('boposts', $options);
}
else 
{
    @include(dirname(__FILE__) . '/en_US_options.php');
    if (WPLANG != '') @include(dirname(__FILE__) . '/' . WPLANG . '_options.php');
    $options = array_merge($boposts_default_options, array_filter(get_option('boposts')));    
}
?>	

<div class="wrap">

    <form method="post">

        <h2>Best Related Posts</h2>
        <p>Here you can configure the layout of the related posts list. To add the list after a posts
        use this code: &lt;?php boposts_show(); ?&gt;.</p>

        <h2>Template</h2>
        <table class="form-table">
            <tr valign="top">
                <th scope="row"><label>Header</label></th>
                <td>
                    <textarea name="options[header]" wrap="off" rows="5" cols="75"><?php echo htmlspecialchars($options['header'])?></textarea>
                </td>
            </tr>
            <tr valign="top">
                <th scope="row"><label>Body</label></th>
                <td>
                    <textarea name="options[body]" wrap="off" rows="5" cols="75"><?php echo htmlspecialchars($options['body'])?></textarea>
                    <br />
                    {title} for the post title, {link} for the post permalink, {excerpt} for the content excerpt, {image} for
                    the first post image url
                </td>
            </tr>
            <tr valign="top">
                <th scope="row"><label>Footer</label></th>
                <td>
                    <textarea name="options[footer]" wrap="off" rows="5" cols="75"><?php echo htmlspecialchars($options['footer'])?></textarea>
                </td>
            </tr>
        </table>

        <h2>Configuration</h2>
        <table class="form-table">
            <tr valign="top">
                <th scope="row"><label>Max posts to show</label></th>
                <td>
                    <input name="options[max]" type="text" size="10" value="<?php echo htmlspecialchars($options['max'])?>"/>
                </td>
            </tr>
            <tr valign="top">
                <th scope="row"><label>Excerpt length</label></th>
                <td>
                    <input name="options[excerpt]" type="text" size="10" value="<?php echo htmlspecialchars($options['excerpt'])?>"/>
                </td>
            </tr>
        </table>

        <p class="submit">
            <input type="submit" name="save" value="Save"/>
            <input type="submit" name="defaults" value="Revert to Defaults" onclick="return confirm('Are you sure?')"/>
        </p>
    </form>
</div>

