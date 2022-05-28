<?php

function sendTelegramMessage($bot,$chatid,$text){
	Logs::addM("sendTelegramMessage");
		$data = array(
		  'chat_id'      => $chatid,
		  'text'    => $text,
		  'is_anonymous' => False
		);

		$options = array(
		  'http' => array(
			'method'  => 'POST',
			'content' => json_encode( $data ),
			'header'=>  "Content-Type: application/json\r\n" .
						"Accept: application/json\r\n"
			)
		);
		
		$url = "https://api.telegram.org/bot$bot/sendMessage";
		$context  = stream_context_create( $options );
		$result = file_get_contents( $url, false, $context );
		$response = json_decode( $result );
		return $response;		
}

function creationPollOnTelegramAndStoreinDb($bot,$platform,$project_name,$project_url,$analyse_url,$analyse_text, $originator,$name,$chatid){
		Logs::addM("creationPollOnTelegramAndStoreinDb");
		$question = "J'investis sur le projet $project_name";
		$option1 = "Non, vraiment aucune envie";
		$option2 = "Oui, sans hésiter";
		$option3 = "J'hésite encore";
		$option4 = "Je ne me prononce pas";
		$options = array($option1,$option2,$option3,$option4);

		$data = array(
		  'chat_id'      => $chatid,
		  'options'    => $options,
		  'question'   => $question,
		  'is_anonymous' => False
		);

		$options = array(
		  'http' => array(
			'method'  => 'POST',
			'content' => json_encode( $data ),
			'header'=>  "Content-Type: application/json\r\n" .
						"Accept: application/json\r\n"
			)
		);
		$url = "https://api.telegram.org/bot$bot/sendPoll";
		$context  = stream_context_create( $options );
		$result = file_get_contents( $url, false, $context );
		$response = json_decode( $result );
		$question = addslashes($response->result->poll->question);
		$pollid = $response->result->poll->id;
		$message_id = $response->result->message_id;
		$option1 = addslashes($response->result->poll->options[0]->text);
		$option2 = addslashes($response->result->poll->options[1]->text);
		$option3 = addslashes($response->result->poll->options[2]->text);
		$query = "INSERT INTO `Poll` (`chatid`,`originator`,`name`,`pollid`,`messageid`,`platform`, `project_name`, `project_url`,`analyse_url`,`analyse_text`, `option1`, `option2`, `option3`,`option4`, `question`, `status`) VALUES ('$chatid','$originator','".addslashes($name)."','$pollid',$message_id,'".addslashes($platform)."','".addslashes($project_name)."','$project_url','$analyse_url','".addslashes($analyse_text)."','$option1','$option2','$option3','$option4','$question','active')";
		Logs::addM($query);
		Mail::generic("creation d'un nouveau vote depuis vote.php",$query,"marc.bluersky@gmail.com");
		$result = sql_query($query);
}

?>