<?php

declare(strict_types=1);

function sf_mp_video_profiles(array $probe): array {
    $height=(int)($probe['video']['height']??1080);
    $profiles=[];
    foreach([[360,640,'800k','96k'],[720,1280,'2800k','128k'],[1080,1920,'5000k','160k']] as [$h,$w,$vb,$ab]){
        if($height+16<$h)continue;$profiles[]=['height'=>$h,'width'=>$w,'video_bitrate'=>$vb,'audio_bitrate'=>$ab,'bandwidth'=>(int)filter_var($vb,FILTER_SANITIZE_NUMBER_INT)*1000+192000];
    }
    return $profiles?:[['height'=>360,'width'=>640,'video_bitrate'=>'800k','audio_bitrate'=>'96k','bandwidth'=>992000]];
}

function sf_mp_process_video_hls(array $object,string $source,string $dir,array $probe):array{
    $ffmpeg=sf_mp_binary_path('ffmpeg');if($ffmpeg==='')return['ok'=>false,'error'=>'ffmpeg_unavailable'];
    $master="#EXTM3U\n#EXT-X-VERSION:3\n";$created=[];
    foreach(sf_mp_video_profiles($probe) as $profile){
        $name=$profile['height'].'p';$profileDir=$dir.'/'.$name;if(!mkdir($profileDir,0750,true)&&!is_dir($profileDir))return['ok'=>false,'error'=>'hls_directory_failed'];
        $playlist=$profileDir.'/index.m3u8';$segment=$profileDir.'/segment_%05d.ts';
        $scale='scale=w='.$profile['width'].':h='.$profile['height'].':force_original_aspect_ratio=decrease,pad='.$profile['width'].':'.$profile['height'].':(ow-iw)/2:(oh-ih)/2';
        $r=sf_mp_run_process([$ffmpeg,'-y','-v','error','-i',$source,'-vf',$scale,'-c:v','libx264','-preset','medium','-profile:v','main','-b:v',$profile['video_bitrate'],'-maxrate',$profile['video_bitrate'],'-bufsize','2M','-g','48','-keyint_min','48','-sc_threshold','0','-c:a','aac','-b:a',$profile['audio_bitrate'],'-ac','2','-ar','48000','-hls_time','6','-hls_playlist_type','vod','-hls_flags','independent_segments','-hls_segment_filename',$segment,$playlist],7200);
        if(empty($r['ok']))return['ok'=>false,'error'=>'video_hls_profile_failed','profile'=>$name,'detail'=>$r['stderr']];
        $master.="#EXT-X-STREAM-INF:BANDWIDTH={$profile['bandwidth']},RESOLUTION={$profile['width']}x{$profile['height']}\n{$name}/index.m3u8\n";
        $created[]=['name'=>$name,'dir'=>$profileDir,'profile'=>$profile];
    }
    $masterPath=$dir.'/master.m3u8';file_put_contents($masterPath,$master);
    $manifest=sf_mp_store_generated_file($object,$masterPath,'manifest','application/vnd.apple.mpegurl','m3u8',['profiles'=>array_column($created,'profile')]);
    if(empty($manifest['ok']))return$manifest;
    foreach($created as $profile){
        $playlistPath=$profile['dir'].'/index.m3u8';
        $variant=sf_mp_store_generated_file($object,$playlistPath,'manifest','application/vnd.apple.mpegurl','m3u8',['variant'=>$profile['name'],'master_object_id'=>$manifest['id'],'profile'=>$profile['profile'],'_parent_object_id'=>$manifest['id']],false);
        if(empty($variant['ok'])) return $variant;
        $sequence=0;foreach(glob($profile['dir'].'/*.ts')?:[] as $segment){sf_mp_store_generated_file($object,$segment,'segment','video/mp2t','ts',['variant'=>$profile['name'],'master_object_id'=>$manifest['id'],'sequence'=>$sequence++,'_parent_object_id'=>$variant['id']],false);}
    }
    return['ok'=>true,'manifest_object_id'=>$manifest['id'],'profiles'=>count($created)];
}

function sf_mp_process_video(array $object,string $source,string $jobType,string $dir,array $probe):array{
    $ffmpeg=sf_mp_binary_path('ffmpeg');if($ffmpeg==='')return['ok'=>false,'error'=>'ffmpeg_unavailable'];
    if($jobType==='video_hls')return sf_mp_process_video_hls($object,$source,$dir,$probe);
    if($jobType==='video_preview'){$out=$dir.'/preview.mp4';$seconds=sf_mp_env_int('SF_MEDIA_VIDEO_PREVIEW_SECONDS',60,5,300);$r=sf_mp_run_process([$ffmpeg,'-y','-v','error','-i',$source,'-t',(string)$seconds,'-vf','scale=w=854:h=480:force_original_aspect_ratio=decrease,pad=854:480:(ow-iw)/2:(oh-ih)/2','-c:v','libx264','-preset','medium','-b:v','1200k','-c:a','aac','-b:a','128k','-movflags','+faststart',$out],3600);if(empty($r['ok']))return['ok'=>false,'error'=>'video_preview_failed','detail'=>$r['stderr']];return sf_mp_store_generated_file($object,$out,'preview','video/mp4','mp4',['preview_seconds'=>$seconds]);}
    $out=$dir.'/poster.jpg';$seek=max(1,min(30,(int)floor(((float)$probe['duration_seconds'])*.1)));$r=sf_mp_run_process([$ffmpeg,'-y','-v','error','-ss',(string)$seek,'-i',$source,'-frames:v','1','-vf','scale=w=1600:h=-2','-q:v','2',$out],600);if(empty($r['ok']))return['ok'=>false,'error'=>'video_poster_failed','detail'=>$r['stderr']];return sf_mp_store_generated_file($object,$out,'poster','image/jpeg','jpg',['seek_seconds'=>$seek]);
}
