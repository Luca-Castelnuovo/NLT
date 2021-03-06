<?php

// CLEAN URL: /u/USERNAME -> /user/profile.php?user_name=$1

require $_SERVER['DOCUMENT_ROOT'] . '/includes/init.php';
loggedin();

$user_name = clean_data($_REQUEST['user_name']);

$logged_in_user = sql_select('users', 'following', "user_id='{$_SESSION['id']}'", true);
$user = sql_select('users', 'user_id,profile_picture,bio,following', "user_name='{$user_name}'", true);

if (empty($user['user_id'])) {
    redirect('/home', 'User doesn\'t exist');
}

$user_is_following_sql = sql_select(
    'users',
    'user_id',
    "following
        LIKE '%,{$user['user_id']},%'
        OR following LIKE '%[{$user['user_id']},%'
        OR following LIKE '%,{$user['user_id']}]%'
        OR following LIKE '%[{$user['user_id']}]%'
    ",
    false
);

$user_is_following = [];
while ($user_following = $user_is_following_sql->fetch_assoc()) {
    array_push($user_is_following, $user_following['user_id']);
}

$followers_count = count($user_is_following);
$following_count = count(json_decode($user['following']));

page_header($user_name);

?>

<div class="row">
    <!-- Phone -->
    <div class="hide-on-med-and-up center">
        <div class="row">
            <div class="col s12">
                <div class="card-panel">
                    <div class="row">
                        <div class="col s12">
                            <img src="<?= $user['profile_picture'] ?>" onerror="this.src='https://cdn.lucacastelnuovo.nl/general/images/profile_picture.png'" class="circle" width="200">
                        </div>
                    </div>
                    <div class="row">
                        <div class="col s12">
                            <h2 class="mt-0"><?= $user_name ?></h2>
                            <?php

                                if ($_SESSION['id'] != $user['user_id']) {
                                    if (in_array($user['user_id'], $logged_in_user['following'])) {
                                        echo <<<HTML
                                        <a onclick="user_undo_follow('{$user_name}')" class="waves-effect waves-light btn grey lighten-5 col s12 black-text tooltipped" data-position="bottom" data-tooltip="Unfollow">Following</a>
HTML;
                                    } else {
                                        echo <<<HTML
                                        <a onclick="user_follow('{$user_name}')" class="waves-effect waves-light btn blue accent-4 col s12">Follow</a>
HTML;
                                    }
                                } else {
                                    echo <<<HTML
                                    <a href="/user/settings" class="waves-effect waves-light btn blue accent-4 col s12">Settings</a>
HTML;
                                }

                            ?>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col s6">
                            <a onclick="user_followers('<?= $user_name ?>')" class="accent-4 blue btn-small pointer waves-effect waves-light col s12"><span class="bold" id="followersNumber"><?= $followers_count ?></span> followers</a>
                        </div>
                        <div class="col s6">
                            <a onclick="user_following('<?= $user_name ?>')" class="accent-4 blue btn-small pointer waves-effect waves-light col s12"><span class="bold" id="followingNumber"><?= $following_count ?></span> following</a>
                        </div>
                    </div>
                    <?php if (!empty($user['bio'])) {
                                ?>
                    <div class="row">
                        <div class="col s12">
                            <p><?= $user['bio'] ?></p>
                        </div>
                    </div>
                    <?php
                            } ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Tablet and up -->
    <div class="hide-on-small-only">
        <div class="row">
            <div class="col s12">
                <div class="card-panel">
                    <div class="row mb-0">
                        <div class="col s5">
                            <img src="<?= $user['profile_picture'] ?>" onerror="this.src='https://cdn.lucacastelnuovo.nl/general/images/profile_picture.png'" class="circle" width="200">
                        </div>
                        <div class="col s7">
                            <div class="row">
                                <div class="col s12">
                                    <h2><?= $user_name ?></h2>
                                    <?php

                                        if ($_SESSION['id'] != $user['user_id']) {
                                            if (in_array($user['user_id'], json_decode($logged_in_user['following']))) {
                                                echo <<<HTML
                                                <a onclick="user_undo_follow('{$user_name}')" class="waves-effect waves-light btn grey lighten-5 col s12 black-text tooltipped" data-position="bottom" data-tooltip="Unfollow">Following</a>
HTML;
                                            } else {
                                                echo <<<HTML
                                                <a onclick="user_follow('{$user_name}')" class="waves-effect waves-light btn blue accent-4 col s12">Follow</a>
HTML;
                                            }
                                        } else {
                                            echo <<<HTML
                                            <a href="/user/settings" class="waves-effect waves-light btn blue accent-4 col s12">Settings</a>
HTML;
                                        }

                                    ?>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col s6">
                                    <a onclick="user_followers('<?= $user_name ?>')" class="accent-4 blue btn-small pointer waves-effect waves-light col s12"><span class="bold" id="followersNumber"><?= $followers_count ?></span> followers</a>
                                </div>
                                <div class="col s6">
                                    <a onclick="user_following('<?= $user_name ?>')" class="accent-4 blue btn-small pointer waves-effect waves-light col s12"><span class="bold" id="followingNumber"><?= $following_count ?></span> following</a>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php if (!empty($user['bio'])) {
                                        ?>
                    <div class="row">
                        <div class="col s12">
                            <p><?= $user['bio'] ?></p>
                        </div>
                    </div>
                    <?php
                                    } ?>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal" id="following_modal">
    <div class="modal-content">
        <div class="row">
            <div class="col s8">
                <h4>Following</h4>
            </div>
            <div class="col s4">
                <a class="btn-floating btn waves-effect waves-light blue accent-4 right modal-close">
                    <i class="material-icons">close</i>
                </a>
            </div>
        </div>
        <div class="row">
            <ul class="collection">
                <div id="following_container"></div>
            </ul>
        </div>
    </div>
</div>

<div class="modal" id="followers_modal">
    <div class="modal-content">
        <div class="row">
            <div class="col s8">
                <h4>Followers</h4>
            </div>
            <div class="col s4">
                <a class="btn-floating btn waves-effect waves-light blue accent-4 right modal-close">
                    <i class="material-icons">close</i>
                </a>
            </div>
        </div>
        <div class="row">
            <ul class="collection">
                <div id="followers_container"></div>
            </ul>
        </div>
    </div>
</div>

<div class="row">
    <div class="col s12">
        <div id="post_container"></div>
    </div>
</div>

<?php

$CSRFtoken = csrf_gen();
$extra = <<<HTML
<script>
    var CSRFtoken = '{$CSRFtoken}';
    var auto_init = false;
    var user_name = '<?= $user_name ?>';

    document.addEventListener('DOMContentLoaded', function() {
        GETrequest(`https://instakilo.lucacastelnuovo.nl/u/${user_name}/feed`, function(response) {
            document.querySelector('#post_container').innerHTML = feed_render_posts_profile(response);
            materialize_init();
            render_hashtags();
        });
    });
</script>
HTML;

?>
<?= page_footer($extra); ?>
