<?php

class TumblPHP{
	public $type;
	public $data;
	public $template;
	public $rendered;
	private $temp_filename;

	public function __construct(){
		if(!isset($_GET['type'])){
			$msg = <<<MSG
Cannot continue as the type of page wasn't set.<br /><br />
Go to <a href = "?type=index">?type=index</a>, <a href = "?type=permalink">?type=permalink</a>, <a href = "?type=search">?type=search</a>, <a href = "?type=tag">?type=tag</a>, or <a href = "?type=day">?type=day</a>
MSG;
			exit($msg);
		}
			

		$this->type = $_GET['type'];
	}
	public function loadData($json_string){
		$json_string = preg_replace(':/\*(.|[\r\n])*?\*/:','',$json_string);
		$this->data = json_decode($json_string);
		if($this->data === NULL){
			exit("JSON data can't be loaded because it's not in the right format.  Try running it through a JSON lint program to see where the problem is");
		}
	}

	public function loadTemplate($template_path){
		if(file_exists($template_path))
			$this->template = file_get_contents($template_path);
		else
			exit("Template file cannot be loaded because the file doesn't exist: ".$template_path);
	}

	public function render($show_rendered_file = FALSE){
		$this->doReplacements();
		$this->write();
		if($show_rendered_file)
			$this->display();
		else
			$this->run();	
	}

	private function doReplacements(){
		$this->rendered = $this->template;

		/* Trim the length of posts to the number desired */
		if($this->type == 'search')
			$this->data->posts = array_slice($this->data->posts,0,$this->data->request->search->SearchResultCount);
		if($this->type == 'tag')
			$this->data->posts = array_slice($this->data->posts,0,$this->data->request->tag->results);

		/* Do post replacements first because if there are any duplicate tokens, they use a regex to only replace the most specific token */
		$this->doPostReplacements();

		$this->doDayPageReplacements();
		$this->doSearchPageReplacements();
		$this->doTagPageReplacements();
		$this->doIndexPageReplacements();
		$this->doPermalinkPageReplacements();

		/* Replace any conditional statements specified by the theme */
		$this->doIfTagReplacements();

		/* These are done last because there are some blog-wide tokens (ie: "Description") that are also used by posts.
		   Those post tokens are only replaced in the context of a post, so after the post tokens are replaced, any occurrences
		   of the duplicate tokens must be refering to the blog-wide tokens
		 */
		$this->doBlogReplacements();
		$this->doGroupBlogReplacements();
		$this->doFollowingReplacements();
		$this->doLikesReplacements();
	}

	/*
	 * Does replacements of strings that represent blog-wide properties
	 */
	private function doBlogReplacements(){
		$this->replace('{MobileAppHeaders}','');

		$this->replace('{block:Description}','<?php if(isset($this->data->Description)): ?>');
		$this->endIfBlock('Description');

		$this->replace('{block:Pagination}','<?php if($this->data->pagination->enabled): ?>');
		$this->endIfBlock('Pagination');

		$this->replace('{block:PreviousPage}','<?php if($this->data->pagination->PreviousPage): ?>');
		$this->endIfBlock('PreviousPage');

		$this->replace('{block:NextPage}','<?php if($this->data->pagination->NextPage): ?>');
		$this->endIfBlock('NextPage');

		$this->replace('{block:SubmissionsEnabled}','<?php if($this->data->blog->SubmissionsEnabled): ?>');
		$this->endIfBlock('SubmissionsEnabled');

		$this->replace('{block:AskEnabled}','<?php if($this->data->blog->AskEnabled): ?>');
		$this->endIfBlock('AskEnabled');

		$this->replace('{CopyrightYears}','2000-'.date('Y'));

		$this->replace('{HeaderImage}',$this->data->blog->values->HeaderImage);
		$this->replace('{AvatarShape}',$this->data->blog->values->AvatarShape);
		$this->replace('{Description}',$this->data->blog->values->Description);
		$this->replace('{MetaDescription}',$this->data->blog->values->Description);
		$this->replace('{PreviousPage}','#previous');
		$this->replace('{NextPage}','#next');
		$this->replace('{CurrentPage}',$this->data->pagination->CurrentPage);
		$this->replace('{TotalPages}',$this->data->pagination->TotalPages);

		foreach($this->data->blog->values as $token=>$replacement){
			$this->replace('{'.$token.'}',$replacement);
		}	
	}

	private function doGroupBlogReplacements(){
		$this->replace('{block:GroupMembers}','<?php if(isset($this->data->blog->GroupMembers): ?>');
		$this->endIfBlock('GroupMembers');

		$this->replace('{block:GroupMember}','<?php foreach($this->data->blog->GroupMembers as $GroupMember): ?>');
		$this->endForeachBlock('GroupMember');

		$this->replace('{GroupMemberName}','<?php echo $GroupMember->GroupMemberName; ?>');
		$this->replace('{GroupMemberTitle}','<?php echo $GroupMember->GroupMemberTitle; ?>');
		$this->replace('{GroupMemberURL}','<?php echo $GroupMember->GroupMemberURL; ?>');
		$this->replace('{GroupMemberPortraitURL-16}','<?php if(isset($GroupMember->{"GroupMemberPortraitURL-16"})){ echo $GroupMember->{"GroupMemberPortraitURL-16"}; } ?>');
		$this->replace('{GroupMemberPortraitURL-24}','<?php if(isset($GroupMember->{"GroupMemberPortraitURL-24"})){ echo $GroupMember->{"GroupMemberPortraitURL-24"}; } ?>');
		$this->replace('{GroupMemberPortraitURL-30}','<?php if(isset($GroupMember->{"GroupMemberPortraitURL-30"})){ echo $GroupMember->{"GroupMemberPortraitURL-30"}; } ?>');
		$this->replace('{GroupMemberPortraitURL-40}','<?php if(isset($GroupMember->{"GroupMemberPortraitURL-40"})){ echo $GroupMember->{"GroupMemberPortraitURL-40"}; } ?>');
		$this->replace('{GroupMemberPortraitURL-48}','<?php if(isset($GroupMember->{"GroupMemberPortraitURL-48"})){ echo $GroupMember->{"GroupMemberPortraitURL-48"}; } ?>');
		$this->replace('{GroupMemberPortraitURL-64}','<?php if(isset($GroupMember->{"GroupMemberPortraitURL-64"})){ echo $GroupMember->{"GroupMemberPortraitURL-64"}; } ?>');
		$this->replace('{GroupMemberPortraitURL-96}','<?php if(isset($GroupMember->{"GroupMemberPortraitURL-96"})){ echo $GroupMember->{"GroupMemberPortraitURL-96"}; } ?>');
		$this->replace('{GroupMemberPortraitURL-128}','<?php if(isset($GroupMember->{"GroupMemberPortraitURL-128")){ echo $GroupMember->{"GroupMemberPortraitURL-128"}; } ?>');

		$swap = [
			"PostAuthorName",
			"PostAuthorTitle",
			"PostAuthorURL",
			"PostAuthorPortraitURL-16",
			"PostAuthorPortraitURL-24",
			"PostAuthorPortraitURL-30",
			"PostAuthorPortraitURL-40",
			"PostAuthorPortraitURL-48",
			"PostAuthorPortraitURL-64",
			"PostAuthorPortraitURL-96",
			"PostAuthorPortraitURL-128",
		];
		$this->straightSwap($swap);
	}

	private function doFollowingReplacements(){
		$this->replace('{block:Following}','<?php if($this->data->following->enabled): ?>');
		$this->endIfBlock('Following');

		$this->replace('{block:Followed}','<?php foreach($this->data->following->Followed as $Followed): ?>');
		$this->endIfBlock('Followed');

		$this->replace('{FollowedName}','<?php echo $Followed->FollowedName; ?>');
		$this->replace('{FollowedTitle}','<?php echo $Followed->FollowedTitle; ?>');
		$this->replace('{FollowedURL}','<?php echo $Followed->FollowedURL; ?>');
		$this->replace('{FollowedPortraitURL-16}','<?php if(isset($Followed->{"FollowedPortraitURL-16"})){ echo $Followed->{"FollowedPortraitURL-16"}; } ?>');
		$this->replace('{FollowedPortraitURL-24}','<?php if(isset($Followed->{"FollowedPortraitURL-24"})){ echo $Followed->{"FollowedPortraitURL-24"}; } ?>');
		$this->replace('{FollowedPortraitURL-30}','<?php if(isset($Followed->{"FollowedPortraitURL-30"})){ echo $Followed->{"FollowedPortraitURL-30"}; } ?>');
		$this->replace('{FollowedPortraitURL-40}','<?php if(isset($Followed->{"FollowedPortraitURL-40"})){ echo $Followed->{"FollowedPortraitURL-40"}; } ?>');
		$this->replace('{FollowedPortraitURL-48}','<?php if(isset($Followed->{"FollowedPortraitURL-48"})){ echo $Followed->{"FollowedPortraitURL-48"}; } ?>');
		$this->replace('{FollowedPortraitURL-64}','<?php if(isset($Followed->{"FollowedPortraitURL-64"})){ echo $Followed->{"FollowedPortraitURL-64"}; } ?>');
		$this->replace('{FollowedPortraitURL-96}','<?php if(isset($Followed->{"FollowedPortraitURL-96"})){ echo $Followed->{"FollowedPortraitURL-96"}; } ?>');
		$this->replace('{FollowedPortraitURL-128}','<?php if(isset($Followed->{"FollowedPortraitURL-128")){ }echo $Followed->{"FollowedPortraitURL-128"}; } ?>');
	}

	private function doLikesReplacements(){
		$this->replace('{block:Likes}','<?php if(this->data->likes->enabled): ?>');
		$this->endIfBlock('Likes');
	}

	/*
	 * Does replacements of strings particular to a day page
	 */
	private function doDayPageReplacements(){
		$this->replace('{block:DayPage}','<?php if($this->type == "day"): ?>');
		$this->endIfBlock('DayPage');
		$this->replace('{block:DayPagination}','<?php if($this->data->pagination->enabled): ?>');
		$this->endIfBlock('DayPagination');
		$this->replace('{block:NextDayPage}','<?php if($this->data->pagination->NextDayPage): ?>');
		$this->endIfBlock('NextDayPage');
		$this->replace('{block:PreviousDayPage}','<?php if($this->data->pagination->PreviousDayPage): ?>');
		$this->endIfBlock('PreviousDayPage');

		$this->replace('{PreviousDayPage}','#previous-day');
		$this->replace('{NextDayPage}','#next-day');
	}

	/*
	 * Does replacements of strings particular to an index page
	 */
	private function doIndexPageReplacements(){
		$this->replace('{block:IndexPage}','<?php if($this->type == "index" || $this->type == "search" || $this->type == "tag" || $this->type == "day"): ?>');
		$this->endIfBlock('IndexPage');
	}

	/*
	 * Does replacements of strings particular to a permalink page
	 */
	private function doPermalinkPageReplacements(){
		$this->replace('{block:PermalinkPage}','<?php if($this->type == "permalink"): ?>');
		$this->endIfBlock('PermalinkPage');
	}

	/*
	 * Does replacements of strings particular to a search page
	 */
	private function doSearchPageReplacements(){
		
		$this->replace('{SearchQuery}','<?php if($this->type == "search"){ echo $this->data->request->search->SearchQuery; } ?>');
		$this->replace('{URLSafeSearchQuery}','<?php if($this->type == "search"){ echo urlencode($this->data->request->search->SearchQuery); } ?>');
		$this->replace('{SearchResultCount}','<?php if($this->type == "search"){ echo $this->data->request->search->SearchResultCount; } ?>');
		$this->replace('{block:SearchPage}','<?php if($this->type == "search"): ?>');
		$this->endIfBlock('SearchPage');
		$this->replace('{block:NoSearchResults}','<?php if($this->data->request->search->results == 0): ?>');
		$this->endIfBlock('NoSearchResults');
	}

	/*
	 * Does replacements of strings particular to a search page
	 */
	private function doTagPageReplacements(){
		$this->replace('{block:TagPage}','<?php if($this->type == "tag"): ?>');
		$this->endIfBlock('TagPage');

		$this->replace('{Tag}',$this->data->request->tag->Tag);
		$url_safe = urlencode($this->data->request->tag->Tag);
		$this->replace('{URLSafeTag}',$url_safe);
		$this->replace('{TagURL}','#'.$url_safe);
		$this->replace('{TagURLChrono}','#chrono-'.$url_safe);

	}

	/*
	 * Some themes may have conditional tags
	 */
	private function doIfTagReplacements(){
		# Do a generic search for all opening "if" blocks
		$pattern = '/\{block:if(.*?)\}/';
		preg_match_all($pattern,$this->rendered,$matches);

		# If matches were found
		if($matches !== FALSE && $matches > 0){
			foreach($matches[0] as $index=>$opening){
				$label = $matches[1][$index];
				$this->replace($opening,'<?php if(isset($this->data->if->'.$label.') && $this->data->if->'.$label.'): ?>');
				$closing = '{/'.substr($opening,1);
				$this->replace($closing,'<?php endif; ?>');
			}
		}
	}

	private function doPostReplacements(){
		$this->doAnswerPostReplacements();
		$this->doAudioPostReplacements();
		$this->doChatPostReplacements();
		$this->doLinkPostReplacements();
		$this->doPanoramaPostReplacements();
		$this->doPhotoPostReplacements();
		$this->doPhotosetPostReplacements();
		$this->doQuotePostReplacements();
		$this->doTextPostReplacements();
		$this->doVideoPostReplacements();
		$this->doPostDateReplacements();


		$this->replace('{block:Posts}','<?php if(count($this->data->posts)): foreach($this->data->posts as $post_index=>$Post): ?>');
		$this->endForeachIfBlock('Posts');

		/* Blocks dependent on post number */
		for($i = 1;$i<= 15;$i++){
			$this->replace('{block:Post'.$i.'}','<?php if($post_index == '.$i.'): ?>');
			$this->endIfBlock('Post'.$i);
		}

		$this->replace('{block:Odd}','<?php if($post_index%2 == 1): ?>');
		$this->endIfBlock('Odd');
		$this->replace('{block:Even}','<?php if($post_index%2 == 0): ?>');
		$this->endIfBlock('Even');

		$this->replace('{block:PostSummary}','<?php if(isset($Post->PostSummary)): ?>');
		$this->endIfBlock('PostSummary');

		$this->replace('{block:PostTitle}','<?php if(isset($Post->PostTitle)): ?>');
		$this->endIfBlock('PostTitle');


		/* Tags */
		$this->replace('{TagURL}','#<?php echo $tag; ?>');
		$postTagPattern = "/(\{block:Tags\}.*?)(\{Tag\})(.*?\{\/block:Tags\})/";
		$this->regexReplace($postTagPattern,'$1<?php echo $tag ?>$3');
		$this->replace('{TagsAsClasses}','<?php if(isset($Post->tags)){ echo implode(" ",$Post->tags); ?> }');

		/* Notes */
		$this->replace('{PostNotes}',$this->generatePostNotes());
		$this->replace('{PostNotes-16}',$this->generatePostNotes(16));
		$this->replace('{PostNotes-64}',$this->generatePostNotes(64));

		/* Like & Reblog buttons */
		$this->doButtonReplacements();

		/* Post specific tokens that aren't relevant to a theme */
		$this->replace('{LikeURL}','#like');
		$this->replace('{ReblogURL}','#reblog');
		$this->replace('{Permalink}','#permalink');
		
		/* These tokens can be straight replaced with the data value - no conditions or formatting necessary */
		$tokens = [
			"BlackLogoURL",
			"Caption",
			"LogoWidth",
			"LogoHeight",
			"NoteCount",
			"NoteCountWithLabel",
			"PostID",
			"PostSummary",
			"PostTitle",
			"PostType",
			"ReblogParentName",
			"ReblogParentTitle",
			"ReblogParentURL",
			"ReblogParentPortraitURL-16",
			"ReblogParentPortraitURL-24",
			"ReblogParentPortraitURL-30",
			"ReblogParentPortraitURL-40",
			"ReblogParentPortraitURL-48",
			"ReblogParentPortraitURL-64",
			"ReblogParentPortraitURL-96",
			"ReblogParentPortraitURL-128",
			"ReblogRootName",
			"ReblogRootTitle",
			"ReblogRootURL",
			"ReblogRootPortraitURL-16",
			"ReblogRootPortraitURL-24",
			"ReblogRootPortraitURL-30",
			"ReblogRootPortraitURL-40",
			"ReblogRootPortraitURL-48",
			"ReblogRootPortraitURL-64",
			"ReblogRootPortraitURL-96",
			"ReblogRootPortraitURL-128",
			"SourceURL",
			"SourceTitle",
			"Submitter",
			"SubmitterPortraitURL-16",
			"SubmitterPortraitURL-24",
			"SubmitterPortraitURL-30",
			"SubmitterPortraitURL-40",
			"SubmitterPortraitURL-48",
			"SubmitterPortraitURL-64",
			"SubmitterPortraitURL-96",
			"SubmitterPortraitURL-128",
			"SubmitterURL",
		];
		$this->straightSwap($tokens);

		/* Blocks 
	     * These are done last, because regex is used to do some token replacements that are duplicates (ie: {Name} is both a chat username, and a link post name)
	     * That regex looks for {block:Chat}(for example), rather than <?php if($Post->PostType == "chat"): ?>
		*/
		$this->replace('{block:Caption}','<?php if(isset($Post->Caption)): ?>');
		$this->endIfBlock('Caption');
		
		$this->replace('{block:ContentSource}','<?php if(isset($Post->SourceURL)): ?>');
		$this->endIfBlock('ContentSource');

		$this->replace('{block:Date}','<?php if($this->type == "permalink"): ?>');
		$this->endIfBlock('Date');

		$this->replace('{block:hasTags}','<?php if(isset($Post->tags)): ?>');
		$this->endIfBlock('hasTags');	
		
		$this->replace('{block:NewDayDate}','<?php if(isset($Post->firstOfDay)): ?>');
		$this->endIfBlock('NewDayDate');

		$this->replace('{block:NoSourceLogo}','<?php if(!isset($Post->BlackLogoURL)): ?>');
		$this->endIfBlock('NoSourceLogo');

		$this->replace('{block:NoteCount}','<?php if(isset($Post->NoteCount) || isset($Post->NoteCountWithLabel)): ?>');		
		$this->endIfBlock('NoteCount');
		
		$this->replace('{block:NotReblog}','<?php if(!isset($Post->ReblogRootURL)): ?>');
		$this->endIfBlock('NotReblog');

		$this->replace('{block:PostNotes}','<?php if($this->type == "permalink" && isset($Post->NoteCount) || isset($Post->NoteCountWithLabel)): ?>');
		$this->endIfBlock('PostNotes');

		$this->replace('{block:RebloggedFrom}','<?php if(isset($Post->ReblogRootURL) || isset($Post->ReblogParentURL)): ?>');
		$this->endIfBlock('RebloggedFrom');
		
		$this->replace('{block:SameDayDate}','<?php if(!isset($Post->firstOfDay)): ?>');
		$this->endIfBlock('SameDayDate');
		
		$this->replace('{block:Source}','<?php if(isset($Post->Source)): ?>');
		$this->endIfBlock('Source');
		
		$this->replace('{block:SourceLogo}','<?php if(isset($Post->BlackLogoURL)): ?>');
		$this->endIfBlock('SourceLogo');
		
		$this->replace('{block:Submission}','<?php if(isset($Post->Submission)): ?>');
		$this->endIfBlock('Submission');

		$this->replace('{block:Tags}','<?php foreach($Post->tags as $tag): ?>');
		$this->endForeachBlock('Tags');

	}


	private function doAnswerPostReplacements(){
		$this->replace('{Replies}','<?php echo $Post->Answer; ?>');

		$swap = [
			"Answer",
			"AnswererPortraitURL-16",
			"AnswererPortraitURL-24",
			"AnswererPortraitURL-30",
			"AnswererPortraitURL-40",
			"AnswererPortraitURL-48",
			"AnswererPortraitURL-64",
			"AnswererPortraitURL-96",
			"AnswererPortraitURL-128",
			"Asker",
			"AskerPortraitURL-16",
			"AskerPortraitURL-24",
			"AskerPortraitURL-30",
			"AskerPortraitURL-40",
			"AskerPortraitURL-48",
			"AskerPortraitURL-64",
			"AskerPortraitURL-96",
			"AskerPortraitURL-128",
			"Question"
		];
		$this->straightSwap($swap);

		$this->replace('{block:Answerer}','<?php if(isset($Post->answered)): ?>');
		$this->endIfBlock('Answerer');
	}

	private function doAudioPostReplacements(){
		/* Audio tokens */
		$this->replace('{AudioEmbed}',$this->generateAudioPlayer(500));
		$this->replace('{AudioEmbed-250}',$this->generateAudioPlayer(250));
		$this->replace('{AudioEmbed-400}',$this->generateAudioPlayer(400));
		$this->replace('{AudioEmbed-500}',$this->generateAudioPlayer(500));
		$this->replace('{AudioEmbed-600}',$this->generateAudioPlayer(600));
		$this->replace('{AudioPlayer}',$this->generateAudioPlayer(500,TRUE));


		$swap = [
			"AlbumArtURL",
			"Album",
			"Artist",
			"ExternalAudioURL",
			"FormattedPlayCount",
			"PlayCount",
			"PlayCountWithLabel",
			"TrackName",
		];
		$this->straightSwap($swap);

		$this->replace('{block:Album}','<?php if(isset($Post->Album)): ?>');
		$this->endIfBlock('Album');
		
		$this->replace('{block:AlbumArt}','<?php if(isset($Post->AlbumArt)): ?>');
		$this->endIfBlock('AlbumArt');

		$this->replace('{block:Artist}','<?php if(isset($Post->Artist)): ?>');
		$this->endIfBlock('Artist');

		$this->replace('{block:Audio}','<?php if($Post->PostType == "audio"): ?>');
		$this->endIfBlock('Audio');

		$this->replace('{block:AudioEmbed}','<?php if(isset($Post->AudioEmbed)): ?>');
		$this->endIfBlock('AudioEmbed');

		$this->replace('{block:ExternalAudio}','<?php if(isset($Post->ExternalAudioURL)): ?>');
		$this->endIfBlock('ExternalAudio');

		$this->replace('{block:PlayCount}','<?php if(isset($Post->PlayCount)): ?>');
		$this->endIfBlock('PlayCount');
		
		$this->replace('{block:TrackName}','<?php if(isset($Post->TrackName)): ?>');
		$this->endIfBlock('TrackName');
	}

	private function doChatPostReplacements(){
		/* Chat tokens */
		$this->replace('{Label}','<?php if(isset($Line->Label)){ echo $Line->Label; }?>');
		$chatNamePattern = "/(\{block:Lines\}.*?)(\{Name\})(.*?\{\/block:Lines\})/";
		$this->regexReplace($chatNamePattern,'$1<?php echo $Line->Name; ?>$3');
		$this->replace('{Line}','<?php echo $Line->Line; ?>');
		$this->replace('{UserNumber}','<?php echo $Line->UserNumber; ?>');
		$this->replace('{Alt}','<?php echo ($line_index%2 == 0) ? "even" : "odd"; ?>');

		$this->replace('{block:Chat}','<?php if($Post->PostType == "chat"): ?>');
		$this->endIfBlock('Chat');

		$this->replace('{block:Label}','<?php if(isset($Line->Label)): ?>');
		$this->endIfBlock('Label');

		$this->replace('{block:Lines}','<?php if(isset($Post->Lines)): foreach($Post->Lines as $line_index=>$Line): ?>');
		$this->endForeachIfBlock('Lines');

		$this->replace('{block:Title}','<?php if(isset($Post->title)): ?>');
		$this->endIfBlock('Title');//Title is also used for Text posts
	}

	private function doLinkPostReplacements(){
		
		$linkNamePattern = "/(\{block:Link\}.*?)(\{Name\})(.*?\{\/block:Link\})/";
		$this->regexReplace($linkNamePattern,'$1<?php echo (isset($Post->Name)) ? $Post->Name : $Post->URL; ?>$3');
		$this->replace('{Host}','<?php echo str_replace("//www.","",$Post->URL); ?>');
		$linkDescriptionPattern = "/(\{block:Link\}.*?)(\{Description\})(.*?\{\/block:Link\})/";
		$this->regexReplace($linkDescriptionPattern,'$1<?php echo $Post->Description; ?>$3');

		$this->replace('{LinkOpenTag}','<?php if(isset($Post->LinkURL)){ echo \'<a href = "\'.$Post->LinkURL.\'">\'; } ?>');
		$this->replace('{LinkCloseTag}','<?php if(isset($Post->LinkURL)){ echo "</a>"; } ?>');

		$swap = [
			"Target",
			"URL"
		];
		$this->straightSwap($swap);

		$this->replace('{block:Host}','<?php if(isset($Post->URL) && isset($Post->Name)): ?>');
		$this->endIfBlock('Host');

		$this->replace('{block:Link}','<?php if($Post->PostType == "link"): ?>');
		$this->endIfBlock('Link');

		$this->replace('{block:Thumbnail}','<?php if(isset($Post->Thumbnail)): ?>');
		$this->endIfBlock('Link');
	}

	private function doPanoramaPostReplacements(){
		$this->replace('{block:Panorama}','<?php if($Post->PostType == "panorama"): ?>');
		$this->endIfBlock('Panorama');
	}

	private function doPhotoPostReplacements(){
		$swap = [
			"PhotoAlt",
			"PhotoHeight-100",
			"PhotoHeight-250",
			"PhotoHeight-400",
			"PhotoHeight-500",
			"PhotoHeight-Panorama",
			"PhotoHeight-HighRes",
			"PhotoURL-100",
			"PhotoURL-250",
			"PhotoURL-400",
			"PhotoURL-500",
			"PhotoURL-75sq",
			"PhotoURL-HighRes",
			"PhotoURL-Panorama",
			"PhotoWidth-Panorama",
			"PhotoWidth-100",
			"PhotoWidth-250",
			"PhotoWidth-400",
			"PhotoWidth-500",
			"PhotoWidth-HighRes"
		];

		$this->straightSwap($swap);

		$this->replace('{block:Aperture}','<?php if(isset($Post->Aperture)); ?>');
		$this->endIfBlock('Aperture');

		$this->replace('{block:Camera}','<?php if(isset($Post->Camera)); ?>');
		$this->endIfBlock('Camera');

		$this->replace('{block:Exif}','<?php if(isset($Post->Camera) || isset($Post->Aperture) || isset($Post->Exposure) || isset($Post->FocalLength)): ?>');
		$this->endIfBlock('Exif');

		$this->replace('{block:Exposure}','<?php if(isset($Post->Exposure)); ?>');
		$this->endIfBlock('Exposure');

		$this->replace('{block:FocalLength}','<?php if(isset($Post->FocalLength)); ?>');
		$this->endIfBlock('FocalLength');

		$this->replace('{block:LinkURL}','<?php if(isset($Post->LinkURL)): ?>');
		$this->endIfBlock('LinkURL');

		$this->replace('{block:HighRes}','<?php if(isset($Post->{"PhotoURL-HighRes"})): ?>');
		$this->endIfBlock('HighRes');

		$this->replace('{block:Photo}','<?php if($Post->PostType == "photo"): ?>');
		$this->endIfBlock('Photo');
	}

	private function doPhotosetPostReplacements(){
		/* 
		   Photosets use the same tokens for each individual photo.  Those tokens get replaced with PHP code that reference $Post,
		   however $Post is used for the whole post, not individual photos.  So, move $Post to a temporary variable, override $Post
		   when looping through the photos, then re-instate $Post after the loop
		 */
		$this->replace('{block:Photos}','<?php $tmpPost = $Post; if(isset($tmpPost->photos)): foreach($tmpPost->photos as $Post): ?>');
		$this->replace('{/block:Photos}','<?php endforeach; endif; $Post = $tmpPost; ?>');
		$this->replace('{Photoset-700}',$this->generatePhotoset(700));
		$this->replace('{Photoset-500}',$this->generatePhotoset(500));
		$this->replace('{Photoset-400}',$this->generatePhotoset(400));
		$this->replace('{Photoset-250}',$this->generatePhotoset(250));

		$this->replace('{block:Photoset}','<?php if($Post->PostType == "photoset"): ?>');
		$this->endIfBlock('Photoset');
	}

	private function doQuotePostReplacements(){
		$swap = [
			"Length",
			"Quote",
			"Source"
		];

		$this->straightSwap($swap);
	}

	private function doTextPostReplacements(){
		$textTitlePattern = "/(\{block:Text\}.*?)(\{Title\})(.*?\{\/block:Text\})/";
		$this->regexReplace($textTitlePattern,'$1<?php echo $Post->title; ?>$3');

		$swap = ['Body'];
		$this->straightSwap($swap);

		$this->replace('{block:Text}','<?php if($Post->PostType == "text"):?>');
		$this->endIfBlock('Text');

		// {block:Title} is replaced in doChatPostReplacements
	}

	private function doVideoPostReplacements(){
		$this->replace('{Video-700}','<?php $this->outputVideo(700,$Post); ?>');
		$this->replace('{Video-500}','<?php $this->outputVideo(500,$Post); ?>');
		$this->replace('{Video-400}','<?php $this->outputVideo(400,$Post); ?>');
		$this->replace('{Video-250}','<?php $this->outputVideo(250,$Post); ?>');

		$this->replace('{VideoEmbed-700}','<?php $this->outputVideo(700,$Post,false); ?>');
		$this->replace('{VideoEmbed-500}','<?php $this->outputVideo(500,$Post,false); ?>');
		$this->replace('{VideoEmbed-400}','<?php $this->outputVideo(400,$Post,false); ?>');
		$this->replace('{VideoEmbed-250}','<?php $this->outputVideo(250,$Post,false); ?>');

		// thumbnail token when multiple thumbnails
		$videoThumbnailsURLPattern = "/(\{block:VideoThumbnails\}.*?)(\{VideoThumbnailURL\})(.*?\{\/block:VideoThumbnails\})/";
		$this->regexReplace($videoThumbnailsURLPattern,'$1<?php echo $videoThumbnailURL; ?>$3');
		// thumbnail token when there's only one
		$this->replace('{VideoThumbnailURL}','<?php if(isset($Post->VideoThumbnailURL)) { echo $Post->VideoThumbnailURL; }');
		
		$this->replace('{block:Video}','<?php if($Post->PostType == "video"): ?>');
		$this->endIfBlock('Video');

		$this->replace('{block:VideoThumbnail}','<?php if(isset($Post->VideoThumbnail)): ?>');
		$this->endIfBlock('VideoThumbnail');

		$this->replace('{block:VideoThumbnails}','<?php if(isset($Post->VideoThumbnail) && count($Post->VideoThumbnail) > 1): foreach($Post->VideoThumbnail as $videoThumbnailURL');
		$this->endForeachIfBlock('VideoThumbnails');
	}



	private function doPostDateReplacements(){
		/* Dates */
		$this->replace('{DayOfMonth}',			'<?php echo date("j",$Post->date); ?>');
		$this->replace('{DayOfMonthWithZero}',	'<?php echo date("d",$Post->date); ?>');
		$this->replace('{DayOfWeek}',			'<?php echo date("l",$Post->date); ?>');
		$this->replace('{ShortDayOfWeek}',		'<?php echo date("D",$Post->date); ?>');
		$this->replace('{DayOfWeekNumber}',		'<?php echo date("N",$Post->date); ?>');
		$this->replace('{DayOfMonthSuffix}',	'<?php echo date("S",$Post->date); ?>');
		$this->replace('{DayOfYear}',			'<?php echo date("z",$Post->date); ?>');
		$this->replace('{WeekOfYear}',			'<?php echo date("W",$Post->date); ?>');
		$this->replace('{Month}',				'<?php echo date("F",$Post->date); ?>');
		$this->replace('{ShortMonth}',			'<?php echo date("M",$Post->date); ?>');
		$this->replace('{MonthNumber}',			'<?php echo date("n",$Post->date); ?>');
		$this->replace('{MonthNumberWithZero}',	'<?php echo date("m",$Post->date); ?>');
		$this->replace('{Year}',				'<?php echo date("Y",$Post->date); ?>');
		$this->replace('{ShortYear}',			'<?php echo date("y",$Post->date); ?>');
		$this->replace('{AmPm}',				'<?php echo date("a",$Post->date); ?>');
		$this->replace('{CapitalAmPm}',			'<?php echo date("A",$Post->date); ?>');
		$this->replace('{12Hour}',				'<?php echo date("g",$Post->date); ?>');
		$this->replace('{24Hour}',				'<?php echo date("G",$Post->date); ?>');
		$this->replace('{12HourWithZero}',		'<?php echo date("h",$Post->date); ?>');
		$this->replace('{24HourWithZero}',		'<?php echo date("H",$Post->date); ?>');
		$this->replace('{Minutes}',				'<?php echo date("i",$Post->date); ?>');
		$this->replace('{Seconds}',				'<?php echo date("s",$Post->date); ?>');
		$this->replace('{Beats}',				'999');
		$this->replace('{Timestamp}',			'<?php echo $Post->date; ?>');
		$this->replace('{TimeAgo}'	,			'2 hours ago');	
	}


	private function doButtonReplacements(){
		$likeButtonPattern = '/(\{LikeButton.*?\})/';
		$reblogButtonPattern = '/(\{ReblogButton.*?\})/';
		$sizePattern = '/size.*?"(\d*)".*/';
		$colorPattern = '/color.*?"(.*?)".*/';

		if($likeButtons = $this->findButtons($likeButtonPattern)){
			foreach($likeButtons as $button){
				$size = $this->getButtonProp($button,$sizePattern);
				$color = $this->getButtonProp($button,$colorPattern);

				$this->replace($button,$this->generateButton('like',$color,$size));
			}
		}

		if($reblogButtons = $this->findButtons($reblogButtonPattern)){
			foreach($reblogButtons as $button){
				$size = $this->getButtonProp($button,$sizePattern);
				$color = $this->getButtonProp($button,$colorPattern);

				$this->replace($button,$this->generateButton('reblog',$color,$size));
			}
		}
	}

	private function findButtons($pattern){
		$count = preg_match_all($pattern,$this->rendered,$matches);
		if($count !== FALSE && $count > 0)
			return $matches[0];
		
		return FALSE;
	}

	private function getButtonProp($markup,$pattern){
		$count = preg_match($pattern,$markup,$matches);
		if($count !== FALSE && $count > 0){
			return $matches[1];
		}
		return FALSE;
	}

	/*
	 * Swaps a token for PHP code that outputs the Post value with the same name
	 */
	private function straightSwap($tokens){
		foreach($tokens as $token){
			$this->replace('{'.$token.'}','<?php if(isset($Post->{"'.$token.'"})){ echo $Post->{"'.$token.'"}; } ?>');
		}	
	}

	/* Close a block that is treated like an "If" */
	private function endIfBlock($token){
		$this->replace('{/block:'.$token.'}','<?php endif; ?>');
	}

	/* Close a block that is treated like a "Foreach" */
	private function endForeachBlock($token){
		$this->replace('{/block:'.$token.'}','<?php endforeach; ?>');
	}

	/* Close a block that is treated like a conditional foreach */
	private function endForeachIfBlock($token){
		$this->replace('{/block:'.$token.'}','<?php endforeach; endif; ?>');
	}


	/* Closes all the blocks that are treated like IF statements */
	private function replaceConditionalEndBlocks(){
		/* End block replacements */

		foreach($this->conditionalEndBlocks as $search){
			$this->replace($search,'<?php endif; ?>');
		}
	}


	private function replace($search,$replace){
		$this->rendered = str_replace($search,$replace,$this->rendered);
	}

	/* Used when a replacement can't be a simple string replacement
	 * IE: When a single token is used in multiple places for different purposes, a regex is needed to determine context
	 */
	private function regexReplace($pattern,$replacement){
		$this->rendered = preg_replace($pattern,$replacement,$this->rendered);
	}

	/*
	 * Generates 50 comments
	 *
	 * Some will be likes, some reblogs, some reblogs with comments (determined at random)
	 */
	private function generatePostNotes($avatar_size = 16){
		$notes = '<ol class = "notes">';
		for($i = 0;$i <= 49; $i++){
			$note_type = rand(0,2);
			$commentary_class = ($note_type == 0) ? 'with_commentary' : 'without_commentary';
			$action_class = ($note_type == 2) ? 'like' : 'reblog';
			$action_verb = ($note_type == 2) ? 'liked' : 'reblogged';

			$blog_title = 'blog_'.rand(111,999);

			$notes .= <<<NOTE
<li class="note $action_class tumblelog_{$blog_title} {$commentary_class}">
	<a rel="nofollow" class="avatar_frame" target="_blank" href="#" title="Link title">
		<img src="http://assets.tumblr.com/images/default_avatar/cone_closed_{$avatar_size}.png" class="avatar " alt="">
	</a>
NOTE;
			// Liked
			if($note_type == 2){
				$notes .= <<<LIKE
<span class="action" data-post-url="#">
	<a rel="nofollow" href="#" class="tumblelog" title="Link title">{$blog_title}</a> likes this
</span>
<div class="clear"></div>
LIKE;
			}

			// Reblogged
			if($note_type == 1 || $note_type == 0){
				$notes .= <<<REBLOG
<span class="action" data-post-url="#">
	<a rel="nofollow" href="#" class="tumblelog" title="Link title">{$blog_title}</a> reblogged this from <a rel="nofollow" href="http://reblog.tumblr.com/" class="source_tumblelog" title="Reblog blog">Reblog blog</a>
</span>
<div class="clear"></div>
REBLOG;
			}

			// Reblogged with comment
			if($note_type == 0){
				$notes .= <<<COMMENT
<blockquote><a rel="nofollow" href="#" title="View post">Comment</a></blockquote>
COMMENT;
			}
			$notes .= '</li>';
		}

		// Original poster
		$notes .= <<<ORIGINAL
	<li class="note reblog tumblelog_blog_original original_post without_commentary">
		<a rel="nofollow" class="avatar_frame" target="_blank" href="http://blog_original.tumblr.com/" title="Original poster">
			<img src="http://assets.tumblr.com/images/default_avatar/cone_closed_{$avatar_size}.png" class="avatar " alt="">
		</a>
		<span class="action" data-post-url="#"><a rel="nofollow" href="#" class="tumblelog" title="Original poster">original-blog</a> posted this</span>
		<div class="clear"></div>
	</li>
</ol>
ORIGINAL;

		return $notes;		
	}

	private function outputVideo($size,$Post,$lightbox=TRUE){
		// Shows a YouTube video of a Halloween Light show set to AWOLNation's Sail
		$video_source = '<iframe width="'.$size.'" height="250" src="https://www.youtube.com/embed/Uhzzta-bJ_s?feature=oembed" frameborder="0" allowfullscreen=""></iframe>';
		
		$lightbox_class = $lightbox ? 'has_lightbox' : '';

		switch($Post->videoType){
			case 'youtube':
				echo $video_source;
				break;			
			case 'tumblr':
				echo '<div id = "tumblr_video_container_'.$Post->PostID.'" class = "tumblr_video_iframe $lightbox_class" style = "width:'.$size.'px;height:250px;">'.$video_source.'</div>';
				break;
		}		
	}

	private function generateAudioPlayer($width,$native=FALSE){
		$native_code = ($native) ? 'class = "tumblr_audio_player"' : '';
		return	'<iframe $native_code width="'.$width.'" height="166" scrolling="no" frameborder="no" src="https://w.soundcloud.com/player/?url=https%3A//api.soundcloud.com/tracks/25596356&amp;color=ff5500&amp;auto_play=false&amp;hide_related=false&amp;show_comments=true&amp;show_user=true&amp;show_reposts=false"></iframe>';
	}

	private function generatePhotoset($width){
		$single_width = $width;
		$double_width = round(($width-10)/2);//10px spacing
		$triple_width = round(($width-20)/3);

		$single_height = round($single_width / 3 * 4);
		$double_height = round($double_width / 3 * 4);
		$triple_height = round($triple_width / 3 * 4);

		return <<<PHOTOSET
<img src = "http://placehold.it/{$single_width}x{$single_height}">
<div style = "margin-top:10px;">
	<img src = "http://placehold.it/{$double_width}x{$double_height}"><img src = "http://placehold.it/{$double_width}x{$double_height}" style = "margin-left:10px;">
</div>
<div style = "margin-top:10px;">
	<img src = "http://placehold.it/{$triple_width}x{$triple_height}"><img src = "http://placehold.it/{$triple_width}x{$triple_height}" style = "margin-left:10px;"><img src = "http://placehold.it/{$triple_width}x{$triple_height}" style = "margin-left:10px;">
</div>
PHOTOSET;
	}

	private function generateButton($type,$colour=FALSE,$size=FALSE){
		switch($colour){
			case 'black':
				$fill = '#000';
				break;
			case 'white':
				$fill = '#FFF';
				break;
			case 'red':
				$fill = '#d95e40';
				break;
			default:
				$fill = '#CCC';
		}
		$size = ($size) ? $size : 20;

		$like = <<<BUTTON
		<svg style = "fill:$fill" width="$size" height="$size" viewBox="0 0 19 16" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" fill="#000000"><path d="M14.0425097,0.000920262799 C16.1435097,-0.0400797372 18.8835097,1.28192026 18.9635097,5.36992026 C19.0525097,9.95492026 15.1985097,13.3079203 9.48350967,16.2089203 C3.76650967,13.3079203 -0.0874903349,9.95492026 0.00150966509,5.36992026 C0.0815096651,1.28192026 2.82150967,-0.0400797372 4.92250967,0.000920262799 C7.02450967,0.0419202628 8.87050967,2.26592026 9.46950967,2.92792026 C10.0945097,2.26592026 11.9405097,0.0419202628 14.0425097,0.000920262799 Z"></path></svg>
BUTTON;
		$reblog = <<<BUTTON
		<svg style = "fill:$fill" width="$size" height="$size" viewBox="0 0 537 512" xmlns="https://www.w3.org/2000/svg" xmlns:xlink="https://www.w3.org/1999/xlink" fill="#ccc"><path d="M 98.893,177.139c0.00-7.462, 4.826-12.275, 12.288-12.275L 405.12,164.864 l0.00,83.469 l 118.72-120.947L 405.12,8.678l0.00,81.51 L 49.382,90.189 c-15.206,0.00-27.648,12.429-27.648,27.648l0.00,171.814 l 77.146-71.603L 98.88,177.139 z M 438.874,332.646c0.00,7.45-4.826,12.275-12.275,12.275L 123.75,344.922 l0.00-83.469 l-116.506,120.922l 116.506,120.947l0.00-81.498 l 356.864,0.00 c 15.206,0.00, 27.648-12.454, 27.648-27.648L 508.262,220.134 l-69.402,71.59L 438.861,332.646 z"></path></svg>
BUTTON;
		$code = <<<CODE
		<div class = "{$type}_button">
			${$type}
		</div>
CODE;
		return $code;
	}

	



	private function write(){
		$this->temp_filename = sys_get_temp_dir().'/tumblPHP-temp.php';
		file_put_contents($this->temp_filename,$this->rendered);
	}

	private function run(){
		include $this->temp_filename;
	}

	private function display(){
		header('Content-Type: text/html; charset=utf-8');
		highlight_file($this->temp_filename);
	}

	public function dump($data){
		echo '<pre>';
		print_r($data);
		echo '</pre>';
	}
}