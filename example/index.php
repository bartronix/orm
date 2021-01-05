<?php

error_reporting(E_ALL);
ini_set('display_errors', '1');
require_once 'Autoloader.php';
$db = new PdoAdapter();
$userMapper = new UserMapper($db);
$articleMapper = new ArticleMapper($db);
//get user by username
$user = $userMapper->findOne(['conditions' => ['username = ? ', 'john doe']]);
echo 'Username: '.$user->username;
echo '<br />Articles of this user:<br />';
foreach ($user->articles as $article) {
    echo $article->title.'<br />';
}
//edit the user
$user->username = 'johnny doe';
$userMapper->update($user);

//add a new user
$newUser = new User();
$newUser->username = 'my username 2';
$userMapper->insert($newUser);

//delete a user
$userMapper->delete(4);

//get users with their articles
//get all users limited by 2, sorted by username ascending and eager load the article relationship
//only 2 queries - one for fetching the users and one for coupling the related articles
$users = $userMapper->findMany(['limit' => 3, 'sort' => ['username', 'asc'], 'relations' => ['Article']]);
//4 queries: one for retrieving the users and 1 per user lazy loading the articles.
$users = $userMapper->findMany(['limit' => 3, 'sort' => ['username', 'asc']]);
echo sizeof($users).' users found<br />';
foreach ($users as $user) {
    echo '<br />Username:'.$user->username.'<br />';
    if (sizeof($user->articles) > 0) {
        echo 'Articles of this user:<br />';
        foreach ($user->articles as $article) {
            echo $article->title.'<br />';
        }
    } else {
        echo 'No articles for this user.';
    }
}
