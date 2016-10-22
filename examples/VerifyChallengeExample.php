<?php

require __DIR__ . '/../vendor/autoload.php';

use NicklasW\PkmGoApi\Authentication\AccessToken;
use NicklasW\PkmGoApi\Authentication\Config\Config;
use NicklasW\PkmGoApi\Authentication\Factory\Factory;
use NicklasW\PkmGoApi\Authentication\Manager;
use NicklasW\PkmGoApi\Kernels\ApplicationKernel;

class VerifyChallengeRequest {
    
    /**
     * Display the verifyChallenge html form.
     */
    public function showForm()
    {
        echo <<<EOFORM
<!DOCTYPE html>
<html lang="en">
    <head>
        <title>Verify Challenge Example</title>
    </head>
    <body>
        <div class="container">
            <center><h1>Verify Challenge Example</h1></center>
            <form id="challengeForm" role="form">
                <div class="form-group">
                    <label class="control-label" for="token">RECaptcha Challenge Token</label>
                    <textarea class="form-control" cols="40" id="token" name="token" rows="10"></textarea>
                    <small id="passwordHelpInline" class="text-muted">
                        The challenge token you submit above is obtained by successfully completing a checkChallenge() api request and solving the subsequent RECaptcha generated by the PGO-Captcha bookmarklet.<br/>
                        You may bypass the GUI form above and post the challenge token directly to this script to recieve a JSON formatted response string.<br/>
                        Ex POST: \$_POST['token']<br/>
                        Ex Response: "{\"user\":\"YOURUSERNAME\",\"success\":true,\"token\":\"YOUR-CHALLENGE-TOKEN\"}"
                    </small>
                </div>
                <div class="form-group">
                    <center><button id="form-submit" type="submit" class="btn btn-lg btn-success">SUBMIT</button></center>
                </div>
            </form>
            <div id="results"></div>
        </div>
        <link href="http://getbootstrap.com/dist/css/bootstrap.min.css" rel="stylesheet">
        <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.12.4/jquery.min.js"></script>
        <script>
            $("#challengeForm").submit(function(event){
                event.preventDefault();
                submitForm();
            });
            function submitForm(){
                var token = $("#token").val();
                $.ajax({
                    type: "POST",
                    url: "VerifyChallengeExample.php",
                    data: "token=" + token,
                    success : function(response){
                        if (response){
                            $("#results").html("<pre>" + JSON.stringify(response) + "</pre>");
                        }
                    }
                });
            }
        </script>
    </body>
</html>
EOFORM;
        exit;
    }
    
    /**
     * Submit the user supplied challenge token to API.
     */
    public function sendToken($token)
    {
        // Initialize error string
        $error = null;
        
        // EXAMPLE Authentication via PTC user credentials
        $config = new Config();
        $config->setProvider(Factory::PROVIDER_PTC);
        $config->setUser('YOURUSERNAME');
        $config->setPassword('YOURPASSWORD');

        // Create the authentication manager
        $manager = Factory::create($config);

        // Initialize the pokemon go application
        $application = new ApplicationKernel($manager);
        if ($application)
        {
            $pogoApi = $application->getPokemonGoApi();
            if ($pogoApi)
            {
                $requestHandler = $pogoApi->getRequestService()->requestHandler();
                if ($requestHandler)
                {
                    $verifyChallenge = new \NicklasW\PkmGoApi\Requests\VerifyChallengeRequest($token);
                    if ($verifyChallenge)
                    {
                        //Needs error checking??
                        $requestHandler->handle($verifyChallenge);
                    
                        $challengeResponse = [
                            "user"      =>	$config->getUser(),
                            "success"   =>	$verifyChallenge->getData()->getSuccess(),
                            "token"     =>	$verifyChallenge->getMessage()->getToken()
                        ];
                    
                        // return JSON encoded challengeResponse
                        echo json_encode($challengeResponse);
                    }else
                        $error = "Theres is a problem with your VerifyChallengeRequest.";
                }else
                    $error = "There is problem with your RequestService.";
            }else
                $error = "There is problem with PokemonGo API.";
        }else
            $error = "Cannot connect with PokemonGo servers.";
            
        if ($error) { echo json_encode(array("ERROR", $error)); }
        
    }//END sendToken()
    
}//END VerifyChallengeRequest

// Initialize VerifyChallengeRequest
$verifyChallengeRequest = new VerifyChallengeRequest();

// Sanatize User Supplied Post Input
$_POST = !empty($_POST) ? filter_input_array(INPUT_POST, FILTER_SANITIZE_STRING) : null;

// Check For Post
if(!$_POST) {
    // Display challengeForm if no postback detected
    $verifyChallengeRequest->showForm();
}else {
    // Check for challenge token
    if($_POST['token']) {
        // Pass challenge token to api
        $verifyChallengeRequest->sendToken($_POST['token']);
    }
    else {
        // POST received but token not specified.
        echo json_encode(array("error", "POST detected but no valid TOKEN value supplied!"));
    }
}
?>