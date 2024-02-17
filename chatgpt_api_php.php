<?php




function requestChatGPT($instructionToChatGPT){
	Logs::addM("start function requestChatGPT");
	// limit chatgpt token usage in DEV environment
	if (strpos(getcwd(), 'DEV') !== false) { $max_tokens=400;} // dev environment
	else{$max_tokens=2000;;} // PROD
	
	
	$req=json_encode(
	array(
"model"=> "gpt-3.5-turbo"
,"messages" =>array(
	array(
	"role"=> "system",
	"content"=> $instructionToChatGPT
	),
	array(
	"role"=> "user",
	"content"=> ""
	)
	)
,
"temperature" => 1
,"max_tokens"=> $max_tokens
,"top_p" => 1
,"frequency_penalty"=> 0
,"presence_penalty"=> 0
),JSON_UNESCAPED_UNICODE
);	

	echo "<br>REQ".$req."<br>";

	$authorization = "Authorization: Bearer ".Properties::$chatgpt_key;	
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, "https://api.openai.com/v1/chat/completions");
	curl_setopt($ch, CURLOPT_POST, 1);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $req);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 1);
	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
	curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json' , $authorization ));
	$res = curl_exec($ch);
	curl_close($ch);
	if(!$res){
		echo "http error";
		Logs::addM("error");
	}else{
		// parse the data
		//$lines = explode("\n", trim($res));
		//var_dump($lines);
		Logs::addM("end function requestChatGPT");
		return $res;
	}
}

function callChatGPTAskForAnalysis($project_obj,$platform_obj,$analyst_obj,$in_lang ){	
		$instruction1 = "Fais une description $in_lang de l'analyse du projet suivant et tu donneras à la fin ton avis sur le projet. Sois sympathique et amusante. Réponds en 400 mots environ.";
		$instruction_html = "\r\n Écris ta réponse en HTML avec des balises H2 pour les sous-titres et des balises p pour les paragraphes.";
		$instruction_analyse = "\r\nAnalyse du projet ".$project_obj->project_name." chez ".$platform_obj->namef." analysé par ".$analyst_obj->name
		."\r\nLe projet aura lieu le ".$project_obj->date_project
		."avis global: ".$project_obj->avis_global."\r\n"
		.$project_obj->analyse_text;
		
		$query_chatgpt1 = $instruction1.$instruction_analyse;
		$query_chatgpt2 = $instruction_html.$instruction1.$instruction_analyse;
		/*
		."\r\nAnalyse du projet ".$project_obj->project_name." chez ".$platform_obj->namef." analysé par ".$analyst_obj->name
		."\r\nLe projet aura lieu le ".$project_obj->date_project
		."avis global: ".$project_obj->avis_global."\r\n"
		.$project_obj->analyse_text;*/
		
		echo "<br><br>".nl2br($query_chatgpt1);		
		$result_chatgpt1 = requestChatGPT($query_chatgpt1);
		var_dump($result_chatgpt1);	
		
		echo "<br><br>".nl2br($query_chatgpt2);	
		$result_chatgpt2 = requestChatGPT($query_chatgpt2);
		var_dump($result_chatgpt2);	
		//echo "<br><br>RESULT raw:<br>";
			
		$result_array1 =json_decode($result_chatgpt1,true); // true to have it in php array
		$result_array2 =json_decode($result_chatgpt2,true); // true to have it in php array
		$content1 = $result_array1['choices'][0]['message']['content'];
		$content2 = $result_array2['choices'][0]['message']['content'];
		
		return array($content1,$content2);	
}



?>