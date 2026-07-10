<?php

declare(strict_types=1);

function sf_mp_generate_waveform(string $source, string $outputJson): array {
    $ffmpeg = sf_mp_binary_path('ffmpeg');
    if ($ffmpeg === '') return ['ok'=>false,'error'=>'ffmpeg_unavailable'];
    $command = [$ffmpeg,'-v','error','-i',$source,'-ac','1','-ar','8000','-f','s16le','pipe:1'];
    $descriptors=[0=>['pipe','r'],1=>['pipe','w'],2=>['pipe','w']];
    $process=proc_open($command,$descriptors,$pipes,null,['PATH'=>'/usr/bin:/bin','LANG'=>'C']);
    if(!is_resource($process))return['ok'=>false,'error'=>'waveform_process_failed'];
    fclose($pipes[0]);
    $bucket=400;$sum=0;$count=0;$peaks=[];$max=1;
    while(!feof($pipes[1])){
        $data=fread($pipes[1],8192);if($data===false||$data==='')continue;
        $samples=unpack('v*',$data)?:[];
        foreach($samples as $sample){$signed=$sample>32767?$sample-65536:$sample;$sum+=abs($signed);$count++;if($count>=$bucket){$value=(int)round($sum/$count);$peaks[]=$value;$max=max($max,$value);$sum=0;$count=0;}}
        if(count($peaks)>12000)break;
    }
    if($count>0){$value=(int)round($sum/$count);$peaks[]=$value;$max=max($max,$value);}
    $stderr=stream_get_contents($pipes[2]);fclose($pipes[1]);fclose($pipes[2]);$exit=proc_close($process);
    if($exit!==0||!$peaks)return['ok'=>false,'error'=>'waveform_decode_failed','detail'=>$stderr];
    $normalized=array_map(static fn(int $v):float=>round($v/$max,4),$peaks);
    $payload=['version'=>1,'sample_rate'=>8000,'bucket_samples'=>$bucket,'peak_count'=>count($normalized),'peaks'=>$normalized];
    if(file_put_contents($outputJson,json_encode($payload,JSON_UNESCAPED_SLASHES))===false)return['ok'=>false,'error'=>'waveform_write_failed'];
    return['ok'=>true,'peak_count'=>count($normalized)];
}

function sf_mp_process_audio(array $object, string $source, string $jobType, string $dir): array {
    $ffmpeg=sf_mp_binary_path('ffmpeg');if($ffmpeg==='')return['ok'=>false,'error'=>'ffmpeg_unavailable'];
    if($jobType==='audio_preview'){
        $out=$dir.'/preview.mp3';$r=sf_mp_run_process([$ffmpeg,'-y','-v','error','-i',$source,'-t',(string)sf_mp_env_int('SF_MEDIA_AUDIO_PREVIEW_SECONDS',30,5,120),'-vn','-ac','2','-ar','44100','-b:a','192k',$out],1800);
        if(empty($r['ok']))return['ok'=>false,'error'=>'audio_preview_failed','detail'=>$r['stderr']];
        return sf_mp_store_generated_file($object,$out,'preview','audio/mpeg','mp3',['preview_seconds'=>sf_mp_env_int('SF_MEDIA_AUDIO_PREVIEW_SECONDS',30,5,120)]);
    }
    if($jobType==='audio_stream'){
        $out=$dir.'/stream.m4a';$r=sf_mp_run_process([$ffmpeg,'-y','-v','error','-i',$source,'-vn','-c:a','aac','-b:a','256k','-movflags','+faststart',$out],3600);
        if(empty($r['ok']))return['ok'=>false,'error'=>'audio_stream_failed','detail'=>$r['stderr']];
        return sf_mp_store_generated_file($object,$out,'stream','audio/mp4','m4a',['bitrate_kbps'=>256]);
    }
    $out=$dir.'/waveform.json';$r=sf_mp_generate_waveform($source,$out);if(empty($r['ok']))return$r;
    return sf_mp_store_generated_file($object,$out,'waveform','application/json','json',['peak_count'=>$r['peak_count']]);
}
