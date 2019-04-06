# phpLogin

include('login.php');

SQl::initDataBase();
$app = new KernelLogicApp();
$app->init();
$token = $_COOKIE['access_token'];

$e =  SQl::getPosts( $token );

foreach ( $e as $i)
{
    echo "<h1>".$i['title']."</h1>";
    echo "<div>".$i['post']."</div>";

}
echo '<pre>';

//header("access_token: $token");
//header('Content-Type: application/json');
//echo json_encode($e);
