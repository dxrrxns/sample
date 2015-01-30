<?php 
$root = $_SERVER['DOCUMENT_ROOT'];
$root = $root."/admin";
include($root."/inc/config.php");
include($root."/inc/functions.php");
checkPagePermissions('liveupdates');

$covID = $_SESSION['covid'];
$db = LTC_PDO();

$sql = "select c.dayOfDesc, t.*, fpct.name[tourneyname], fpct.buyin, tgrp.groupname ".
"from coverage c with (nolock) ".
"inner join tournament t with (nolock) on c.fpctournamentID = t.fpctournamentID ".
"inner join fpc.dbo.tournament fpct with (nolock) on fpct.id = t.fpctournamentID ".
"inner join fpc.dbo.tournamentGrouping tgrp with (nolock) on tgrp.tourneygroupid = fpct.groupid ".
"WHERE c.covID = :covID ";

$sql = $db->prepare($sql);
$sql->bindparam(':covID',$covID);
$sql->execute();
while ($res = $sql->fetch(PDO::FETCH_ASSOC)) {
	$dayOf = $res['dayOfDesc'];
	$tName = $res['tourneyname'];
	$gName = $res['groupname'];
	$timeDiff = $res['timeDiff'];
}

$postID = $_REQUEST['pid'];
// GET GENERAL POST INFO
$sql = 	"SELECT * ".
		"FROM posts p ".
		"LEFT OUTER JOIN postsBoardCards c ON c.postID = p.postID ".
		"WHERE p.postID = :postID ; ";
$sql = $db->prepare($sql);
$sql->bindParam(':postID',$postID);
$sql->execute();

$postArray = $sql->fetch(PDO::FETCH_ASSOC);
$title = $postArray['postTitle'];
$content = $postArray['postText'];
$pubDate = new DateTime($postArray['publishDate']);
$pubDate = $pubDate->format('m/d/Y g:i A');
$status = $postArray['statusID'];
if ($postArray['bluffOnly'] == true) {
	$bluffonly = 1;
} else {
	$bluffonly = 0;
}
$covID = $postArray['covID'];
if ($postArray['flop1'] == ''){
	$flop = '';	
} else {
	$flop = $postArray['flop1'].' '.$postArray['flop2'].' '.$postArray['flop3'];	
}
$turn = $postArray['turn'];
$river = $postArray['river'];
$blindLevel = $postArray['blindLevel'];
$smallBlind = $postArray['smallBlind'];
$bigBlind = $postArray['bigBlind'];
$ante = $postArray['ante'];

// GET TAGS
$sql = "SELECT t.specTagID,s.specTagDesc FROM postsSpecialTags t INNER JOIN specialTags s on t.specTagID = s.specTagID WHERE t.postID = :postID ; ";
$sql = $db->prepare($sql);
$sql->bindParam(':postID',$postID);
$sql->execute();
$i = 0;
while ($tRow = $sql->fetch(PDO::FETCH_ASSOC)){
	$tags[$i] = $tRow;
	$i++;
}
if (isset($tags)){
	$tagsCnt = count($tags);
} else {
	$tagsCnt = 0;
}
// GET PLAYER INFO
$sql = "SELECT pp.*,fpcpp.firstName+' '+fpcpp.lastName as fullName,pp.beginBal,pp.endBal ".
		"FROM postsPlayers pp ".
		"LEFT OUTER JOIN fpc.dbo.pokerplayers fpcpp ON pp.playerID = fpcpp.ID ".
		"WHERE pp.postID = :postID ; ";		
$sql = $db->prepare($sql);
$sql->bindParam(':covID', $covID);
$sql->bindParam(':postID', $postID);
$sql->execute();
$i = 0;
while ($pRow = $sql->fetch(PDO::FETCH_ASSOC)){
	$players[$i] = $pRow;
	$i++;
}
if (isset($players))  
  $playersCnt = count($players);
else
  $playersCnt = 0;
?>
<!DOCTYPE html>
<html lang="en">

<head>
	<?php include($root."/inc/com_meta.php"); ?>
    <!-- Page Specific Meta -->
    <meta name="description" content="">
    <meta name="author" content="">
    <!-- Page Title -->
    <title>Live Update Post Edit | BLUFF Admin</title>
    <!-- CSS -->
    <?php include($root."/inc/com_css.php"); ?>
    <link rel="stylesheet" href="/admin/css/bootstrap-datetimepicker.min.css" />
    <?php include($root."/inc/com_responsive.php"); ?>
</head>

<body id="edit" data-o_tags="">
	<?php include($root."/inc/social_open.php"); ?>
    <div id="wrapper">
        <!-- Navigation -->
        
		<?php 
		$nav_section = "posts";
		include($root."/inc/navigation.php");?>

        <div id="page-wrapper">

            <div class="container-fluid">

                <!-- Page Heading -->
                <div class="row">
                    <div class="col-lg-12">
                        <h1 class="page-header">
                            Post <small>Live Reporting Entry</small>
                        </h1>
                        <ol class="breadcrumb">
                            <li class="active">
                                <i class="fa fa-pencil"></i> Posts / Edit Post Entry - <strong><?php echo $gName .' | ' .$tName .' | ' .$dayOf; ?></strong>
                            </li>
                        </ol>
                    </div>
                </div> 
                
                <div class="row">
                
                	<div class="col-sm-12">                    	
                        
                        <div class="row">                       	
                            <div class="form-group form-inline col-sm-6">
                            <label for="postTxt" style="display:block;">Status:</label>
                            <select id="status" class="form-control">
                            	<option value="1" <?php if ($status == 1){ echo 'selected="selected"';}?> >Draft</option>
				<?php if ($status==2) echo '<option value="2" selected="selected"'; ?>>Pending</option>';  
                                <option value="3" <?php if ($status == 3){ echo 'selected="selected"';}?> >Published</option>
				<?php if ($status==4) echo '<option value="4" selected="selected"'; ?>>Trashed</option>';  
                            </select>
                            </div>
                            
                            <div class="form-group form-inline col-sm-6">
                            <label for="view" style="display:block;">Viewable:</label>
                            <select id="view" class="form-control">
                            	<option value="0" <?php if ($bluffonly == 0){ echo 'selected="selected"';}?> >Public</option>
                                <option value="1" <?php if ($bluffonly == 1){ echo 'selected="selected"';}?> >BLUFF Only</option>
                            </select>
                            </div>
                            
                            <div class="form-group col-sm-6">
                            <label for="postTitle">Title:</label>
                        	<input type="text" class="form-control" id="postTitle" name="postTitle" placeholder="Enter Title..." value="<?php echo $title;?>">
                            </div>
                            
                            <div class="form-group col-sm-6" style="width:300px;">
                            	<label for="postDate">Post Date/Time:</label>
                                <div class='input-group date' id='datetimepicker1'>
                                    <input id="postDate" name="postDate" type='text' class="form-control" value="<?php echo $pubDate;?>" />
                                    <span class="input-group-addon"><span class="glyphicon glyphicon-calendar"></span>
                                    </span>
                                </div>
                            </div>
                            
                            <div class="form-group col-sm-12">
                            <label for="postTxt">Post Content:</label>
                            <textarea id="postTxt" class="editor"><?php echo $content;?></textarea>
                            </div>
                            
                            <div class="form-group form-inline col-sm-12">
                            <label for="postTxt" style="display:block;">Tags:</label>
                            <input id="tag" name="tag" class="form-control " type="text" placeholder="..." style="width:20rem;margin-right:2rem;" data-tagID="0"><button id="addTag" class="btn btn-success">Add Tag</button>
                            <div id="tags" style="margin-top:1rem;">
                            	<?php 
								$tagIDs ="";
								for ($c=0; $c < $tagsCnt; $c++){
									echo '<span class="label label-primary" style="margin-right:10px;" id="tag'.$tags[$c]['specTagID'].'" >'.$tags[$c]['specTagDesc'].' <a style="color:#009;cursor:pointer;" class="removetag" data-rmv="'.$tags[$c]['specTagID'].'">x</a></span>';
									$tagIDs[$c] = $tags[$c]['specTagID'];																											
								}
								?>
                            </div>
                            </div>
                            
                            <div class="col-sm-12">
                            <label for="handSummary">Hand Summary:</label>
                                <div class="players_cont">
                                <?php if ($playersCnt == 0){?>
                                <div class="well well-sm plrfld">
                                <label for="handSummary">Player 1:</label>
                                <div class="form-group">
                                  <div class="input-group">
                                    <div class="input-group-addon"><i class="fa fa-user"></i> 1</div>
                                    <input id="p1" class="form-control input-sm col-xs-10 playername" type="text" placeholder="Unknown" data-fpcid="0" data-playNum="1">
                                    <div class="input-group-addon"><i class="fa fa-minus-square"></i> <i class="fa fa-minus-square"></i></div>
                                    <input id="h1" class="form-control input-sm" type="text" placeholder="0x 0x">
                                  </div>
                                  
                                </div>
                                
                                <div class="form-group">
                                  <div class="input-group">
                                    <div class="input-group-addon"><i class="fa fa-database"></i> S</div>
                                    <input id="s1" class="form-control input-sm" type="text" placeholder="0">                                    
                                    <div class="input-group-addon"><i class="fa fa-database"></i> E</div>
                                    <input id="e1" class="form-control input-sm" type="text" placeholder="0">                                    
                                  </div>                                  
                                </div>
                                
                                </div>
                                
                                <div class="well well-sm plrfld">
                                <label for="handSummary">Player 2:</label>
                                <div class="form-group">
                                  <div class="input-group">
                                    <div class="input-group-addon"><i class="fa fa-user"></i> 2</div>
                                    <input id="p2" class="form-control input-sm col-xs-10 playername" type="text" placeholder="Unknown" data-fpcid="0" data-playNum="2">
                                    <div class="input-group-addon"><i class="fa fa-minus-square"></i> <i class="fa fa-minus-square"></i></div>
                                    <input id="h2" class="form-control input-sm" type="text" placeholder="0x 0x">
                                  </div>                                 
                                </div>
                                
                                <div class="form-group">
                                  <div class="input-group">
                                    <div class="input-group-addon"><i class="fa fa-database"></i> S</div>
                                    <input id="s2" class="form-control input-sm" type="text" placeholder="0">                                    
                                    <div class="input-group-addon"><i class="fa fa-database"></i> E</div>
                                    <input id="e2" class="form-control input-sm" type="text" placeholder="0">                                    
                                  </div>
                                </div>                            
                                
                                </div>
                                <?php } else {
									for ($c=0; $c < $playersCnt; $c++){
										$p = $c+1;
										$pcards = "";
										for ($z=1; $z<=7; $z++){
											if ($players[$c]['card'.$z] != ""){
												$pcards = $pcards.$players[$c]['card'.$z]." ";
											}
										}
										$pcards = trim($pcards);
										echo '<div class="well well-sm plrfld">';
										echo '<label for="handSummary">Player '.$p.':</label>';
										echo '<div class="form-group">';
										echo '<div class="input-group">';
										echo '<div class="input-group-addon"><i class="fa fa-user"></i> '.$p.'</div>';
										echo '<input id="p'.$p.'" class="form-control input-sm col-xs-10 playername" type="text" placeholder="Unknown" data-fpcid="'.$players[$c]['playerID'].'" data-playNum="'.$p.'" value="'.$players[$c]['fullName'].'">';
										echo '<div class="input-group-addon"><i class="fa fa-minus-square"></i> <i class="fa fa-minus-square"></i></div>';
										echo '<input id="h'.$p.'" class="form-control input-sm" type="text" placeholder="0x 0x" value="'.$pcards.'">';
										echo '</div>';
										echo '</div>';										
										echo '<div class="form-group">';
										echo '<div class="input-group">';
										echo '<div class="input-group-addon"><i class="fa fa-database"></i> S</div>';
										echo '<input id="s'.$p.'" class="form-control input-sm" type="text" placeholder="0" value="'.$players[$c]['beginBal'].'">';
										echo '<div class="input-group-addon"><i class="fa fa-database"></i> E</div>';
										echo '<input id="e'.$p.'" class="form-control input-sm" type="text" placeholder="0" value="'.$players[$c]['endBal'].'">';
										echo '</div>';
										echo '</div>';										
										echo '</div>';
									}
								}?>
                                </div>
                                <button id="btn_add_player" class="btn btn-default" style="margin-bottom:2rem;display:inline-block;margin-right:1rem;"> + <i class="fa fa-user"></i> Additional Player</button>
                                <button id="btn_rmv_player" class="btn btn-default" style="margin-bottom:2rem;display:inline-block" disabled> - <i class="fa fa-user"></i> Remove Player</button>
                                
                                <div class="well well-sm">
                                <label for="handSummary">Board:</label>
                                <div class="form-group">
                                  <div class="input-group">
                                    <div class="input-group-addon">Flp</div>
                                    <input id="flop" class="form-control input-sm col-xs-10" type="text" placeholder="0x 0x 0x" value="<?php echo $flop;?>">
                                    <div class="input-group-addon">Trn</div>
                                    <input id="turn" class="form-control input-sm" type="text" placeholder="0x"value="<?php echo $turn;?>">
                                    <div class="input-group-addon">Rvr</div>
                                    <input id="river" class="form-control input-sm" type="text" placeholder="0x"value="<?php echo $river;?>">
                                  </div>
                                </div>
                                </div>

				<?php if (isset($_REQUEST['pid'])) { ?>
                                <div class="well well-sm">
                                <label for="tournament-levels">Tournament Information:</label>
                                <div class="form-group" style="width:55%;">
                                  <div class="input-group">
                                    <div class="input-group-addon">Blind Level</div>
                                    <input id="blindLevel" class="form-control type="text" value="<?php echo $blindLevel;?>">
                                    <div class="input-group-addon">Small Blind</div>
                                    <input id="smallBlind" class="form-control type="text" value="<?php echo $smallBlind;?>">
                                    <div class="input-group-addon">Big Blind</div>
                                    <input id="bigBlind" class="form-control type="text" value="<?php echo $bigBlind;?>">
                                    <div class="input-group-addon">Ante</div>
                                    <input id="postante" class="form-control type="text" value="<?php echo $ante;?>">
                                  </div>
                                </div>
                                </div>
				<?php } else { ?>
                                    <input id="blindLevel" class="form-control type="hidden" value="-1">
                                    <div class="input-group-addon">Small Blind</div>
                                    <input id="smallBlind" class="form-control type="hidden" value="-1">
                                    <div class="input-group-addon">Big Blind</div>
                                    <input id="bigBlind" class="form-control type="hidden" value="-1">
                                    <div class="input-group-addon">Ante</div>
                                    <input id="postante" class="form-control type="hidden" value="-1">
				<?php } ?>
                        </div>
                        <div class="col-xs-6"><button id="btn_publish" class="btn btn-info" style="width:100%;margin-bottom:2rem;">UPDATE</button></div>
                                <div class="col-xs-6"></div>
                                <div id="returnLog" class="col-xs-12"></div>
                    </div>
                    
                </div>

                <div class="row">
                    <div class="col-sm-12">
                    
                    <!--Post Content:<br>
                    <textarea name="txtPost" id="txtPost" rows="10" cols="80" class="editor" >This is my textarea to be replaced with CKEditor.</textarea>-->
                    </div>
                </div>
                <!-- /.row -->               

            </div>
            <!-- /.container-fluid -->

        </div>
        <!-- /#page-wrapper -->

    </div>
    <!-- /#wrapper -->	
    <?php include($root."/inc/com_js.php"); ?>	
    	
	<script>	
	// ATTACH TAG FUNCTION
	function attachTag(tid,tdesc){
		var t = $("#tag");
		var s = $("#tags");
		t.val('');
		t.data('tagID',0);	
		s.data('tagArr').push(tid);
		$("#tags").append('<span class="label label-primary" style="margin-right:10px;" id="tag'+tid+'" >'+tdesc+' <a style="color:#009;cursor:pointer;" class="removetag" data-rmv="'+tid+'">x</a>');
		console.log("ATTACH TAG -> tid: " + tid + " tdesc: " + tdesc);
		console.log("CURRENT FIELD VAL -> tid: " + t.data('tagID') + " tdesc: " + t.val());	
		console.log("CURRENT ARRAY VAL -> tagArr: " + s.data('tagArr'));	
	}		
	// REMOVE TAG FROM ARRAY
	$("#tags").on('click', '.removetag', function(){
		var rmvTag = $(this).data('rmv');
		var curTags = $('#tags').data('tagArr');
		//var oldTags = $('#tags').data('o_tagArr');
		var oldTags = [<?php if (is_array($tagIDs) )echo '"'.implode('","',  $tagIDs ).'"'; ?>];		
		var indexOfArray = curTags.indexOf(''+rmvTag+'');
		console.log('Array Index: '+indexOfArray);
		var newTags = curTags;
		newTags.splice(indexOfArray,1);		
		$('#tags').data('tagArr',newTags);
		$('#tags').data('o_tagArr',oldTags); // FIX WEIRD DATA CHANGING ON SPLICE
		$("#tag"+rmvTag).remove();		
	});
	$(function() {
		// INITIATE WYSIWYG COMPONENT
		$( 'textarea.editor' ).ckeditor();
		
		// INITIATE DATE | TIME PICKER
		$('#datetimepicker1').datetimepicker();
		
		/*******************************************************************************************
		** TAG INTERFACE ***************************************************************************
		*******************************************************************************************/
				
		// SET TAG ARRAY
		var tagArr = [<?php if (is_array($tagIDs) )echo '"'.implode('","',  $tagIDs ).'"'; ?>];
		$("#tags").data('tagArr',tagArr);		
		
		// TAG AUTO COMPLETE
		$( "#tag" ).autocomplete({
		  source: function( request, response ) {
			$.ajax({
			  url: "/admin/ajax/get_tag.php",
			  dataType: "json",
			  data: {q: request.term},
			  success: function(data) {
				  response($.map(data, function(item) {
				  return {
					  label: item.specTagDesc,
					  id: item.specTagID
					  };
				  }));
			  }
			});
			
			// ANY ON TYPE RESET ACTIONS HERE
			$("#tag").data('tagID',0);
		  },
		  minLength: 2,
		  select: function( event, ui ) {			
			attachTag(ui.item.id,ui.item.label);
			$(this).val(''); return false;
			$(this).data('tagID',0); return false;	
		  }		  
		});
		
		// ATTACH TAG ID TO ... ADD NEW TAGS
		$("#addTag").click(function() {
			var tagID = $("#tag").data('tagID');
			var tagDesc = $("#tag").val();
			if (tagDesc.length > 2){
				if (tagID == 0) {
					// TAG DOES NOT CURRENLTY EXIST ... ADD NEW
					$.ajax({
					  type: "POST",
					  url: "/admin/ajax/add_new_tag.php",				  
					  data: {t: tagDesc},
					  success: function(data) {
							attachTag(data,tagDesc);
							$("#tag").removeClass( "ui-autocomplete-loading" );			  
					  }
					});
				} else {
					attachTag(tagID,tagDesc);
				}
			}
		});
		
		/*******************************************************************************************
		** PLAYERS INTERFACE ***********************************************************************
		*******************************************************************************************/
		
		// PLAYER NAME AUTO COMPLETE
		$(".players_cont").on("keydown",function(){
			$(".playername").autocomplete({
			  source: function( request, response ) {
				$.ajax({
				  url: "/admin/ajax/get_player.php",
				  dataType: "json",
				  data: {q: request.term, covid: <?php echo $covID; ?>},
				  success: function(data) {
					  response($.map(data, function(item) {
					  return {
						  label: item.name,
						  ID: item.ID,
						  chipCountID: item.chipCountID,
						  beginBal: item.beginBal,
						  endBal: item.endBal
						  };
					  }));
				  }
				});
				//$("#insert").prop("disabled",true);
				//$("#update").prop("disabled",true);
			  },
			  minLength: 2,
			  select: function( event, ui ) {			
				$(this).data('fpcid',ui.item.ID);
				console.log($(this).data('fpcid'));
				if (ui.item.chipCountID == null) {
					//$("#insert").prop("disabled",false);
				} else {
					//$("#update").prop("disabled",false);				
					var name = $(this).attr('id');
					var pNum = name.replace("p","s");
					$('#'+pNum).val(ui.item.beginBal);
				}
			  }		  
			});
		  });
		});
	  
	  // ADD ADDITIONAL PLAYER FIELDS
	  $("#btn_add_player").click(function(){
		 var eCnt = $(".plrfld").length;
		 var pCnt = eCnt + 1;
		 var newPlayer = '<div class="well well-sm plrfld">';
		 	 newPlayer += '<label for="handSummary">Player '+pCnt+':</label>';
			 newPlayer += '<div class="form-group">';
			 newPlayer += '<div class="input-group">';
			 newPlayer += '<div class="input-group-addon"><i class="fa fa-user"></i> '+pCnt+'</div>';
			 newPlayer += '<input id="p'+pCnt+'" class="form-control input-sm col-xs-10 playername" type="text" placeholder="Unknown" data-fpcid="0" data-playNum="'+pCnt+'">';
			 newPlayer += '<div class="input-group-addon"><i class="fa fa-minus-square"></i> <i class="fa fa-minus-square"></i></div>';
			 newPlayer += '<input id="h'+pCnt+'" class="form-control input-sm" type="text" placeholder="0x 0x"></div></div>';
			 newPlayer += '<div class="form-group"><div class="input-group">';
			 newPlayer += '<div class="input-group-addon"><i class="fa fa-database"></i> S</div>';
			 newPlayer += '<input id="s'+pCnt+'" class="form-control input-sm" type="text" placeholder="0">';
			 newPlayer += '<div class="input-group-addon"><i class="fa fa-database"></i> E</div>';
			 newPlayer += '<input id="e'+pCnt+'" class="form-control input-sm" type="text" placeholder="0"></div></div>';
			 newPlayer += '</div>'; 
		 $(".players_cont").append(newPlayer); 
		 if (pCnt > 2){$("#btn_rmv_player").prop('disabled',false);}
		 if (pCnt == 9){$(this).prop('disabled',true);}
	  });
	  
	  // REMOVE PLAYER
	  $("#btn_rmv_player").click(function(){
		var eCnt = $(".plrfld").length;
		//alert(eCnt);
		$('.plrfld:nth-of-type('+eCnt+')').remove();
		if (eCnt <= 3){$(this).prop('disabled',true);}
		if (eCnt == 9){$("#btn_add_player").prop('disabled',false);}  
	  });
	  
	  /*******************************************************************************************
	  ** SET ORIGINAL VALUES *********************************************************************
	  *******************************************************************************************/
	  $("#edit").data("o_status",$('#status').val());
	  $("#edit").data("o_title",$('#postTitle').val());
	  $("#edit").data("o_postDT",$('#postDate').val());
	  $("#edit").data("o_view",$('#view').val());
	  $("#edit").data("o_content",$('textarea.editor').val());
	  $("#edit").data("o_board",[$('#flop').val(),$('#turn').val(),$('#river').val()]);

	  $("#edit").data("o_blindLevel",$('#blindLevel').val());
	  $("#edit").data("o_smallBlind",$('#smallBlind').val());
	  $("#edit").data("o_bigBlind",$('#bigBlind').val());
	  $("#edit").data("o_postante",$('#postante').val());
	  
	  //alert($('#edit').data('o_title'));
	  var orig_players = []
	  var j = 1, i = 0;			
			while (i < 9) {
				if ($('#p' + j).length){
					orig_players[i] = [$('#p' + j).data('fpcid'),$('#h' + j).val(),$('#s' + j).val(),$('#e' + j).val(),true]
				}
				i++; j++;
			}	  
	  $("#edit").data("o_players",orig_players);
	  
	  /*******************************************************************************************
	  ** FORM SUBMIT *****************************************************************************
	  ********************************************************************************************/
	  $("#btn_publish").click(function() {
			// GRAB ORIGINAL DATA VARIABLES
			var o_status = $("#edit").data("o_status");
			var o_title = $("#edit").data("o_title");
			var o_postDT = $("#edit").data("o_postDT");
			var o_view = $("#edit").data("o_view");
			var o_content = $("#edit").data("o_content");
			var o_tags = [<?php if (is_array($tagIDs) )echo '"'.implode('","',  $tagIDs ).'"'; ?>];
			var o_board = $("#edit").data("o_board");
			var o_players = $("#edit").data("o_players");
			var o_postid = <?php echo $postID; ?>;
			var o_blindLevel = 1;
			var o_bigBlind = 1;
			var o_smallBlind = 1;
			var o_postante = 1;
			
			// SET POST DATA VARIABLES
			var covID = <?php echo $covID; ?>;
			var status = $('#status').val();
			var view = $('#view').val();
			var title = $('#postTitle').val();
			var postDT = $('#postDate').val();
			var postblindLevel = $('#blindLevel').val();
			var postsmallBlind = $('#smallBlind').val();
			var postbigBlind = $('#bigBlind').val();
			var postante = $('#postante').val();

			var content = $('textarea.editor').val();
			var tags = $("#tags").data('tagArr');
			var board = [$('#flop').val().toLowerCase(),$('#turn').val().toLowerCase(),$('#river').val().toLowerCase()];
			var players = [];
			// BUILD PLAYERS ARRAY FOR ALL AVAILABLE PLAYERS
			var j = 1, i = 0;			
			while (i < 9) {
				if ($('#p' + j).length){				    
					//===== THE FOLLOWING LINE WILL PREVENT THE SYSTEM FROM INSERTING THE UNKNOWN PLAYER WHEN THE USER LEAVES EVERYTHING BLANK =====================================
				    if (($('#p' + j).data('fpcid') != '0')) {
					players[i] = [$('#p' + j).data('fpcid'),$('#h' + j).val(),$('#s' + j).val(),$('#e' + j).val(),true]
				    }
				}
				i++; j++;
			}
			$.ajax({
			  type: "POST",
			  url: "/admin/ajax/edit_post.php",
			  //dataType: "json",
			  data: {covid: covID,title: title,pubdate: postDT,content: content,tags: tags,board: board,players: players,status: status,view: view,o_postid: o_postid,o_status: o_status,o_title: o_title,o_postDT: o_postDT,o_view: o_view,o_content: o_content,o_tags: o_tags,o_board: o_board,o_players: o_players, postblindLevel: postblindLevel, postsmallBlind: postsmallBlind, postbigBlind: postbigBlind, postante: postante, o_postblindLevel: o_blindLevel, o_postbigBlind: o_bigBlind, o_postsmallBlind: o_smallBlind, o_postante: o_postante, passPlayerCt: <?php echo $playersCnt ?>},
			  success: function(data) {			  				 				 
				window.location.href = "/admin/posts/edit.php?pid=" + data;
			  }
			});
			
		});	
		
		CKEDITOR.replace( 'editor', {
    plugins: 'wysiwygarea,toolbar,sourcearea,image,basicstyles,iframe,youtube',
    on: {
        instanceReady: function() {
            this.dataProcessor.htmlFilter.addRules( {
                elements: {
                    img: function( el ) {
                        if ( !el.attributes.class )
                            el.attributes.class = 'img-responsive';
                    }
                }
            } );            
        }
    }
} );
	</script>
    <!-- MODAL WINDOW FOR ADDING NEW PLAYER -->
    <?php include($root."/inc/modal_new_player.php"); ?>
</body>

</html>