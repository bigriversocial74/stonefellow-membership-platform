<?php
/** Secure upload helpers available to the admin catalog. */
if (!function_exists('sf_admin_upload_rules')) {
function sf_admin_upload_rules(): array { return [
 'image'=>['max_bytes'=>12582912,'extensions'=>['jpg','jpeg','png','webp','gif'],'mimes'=>['image/jpeg','image/png','image/webp','image/gif']],
 'audio'=>['max_bytes'=>157286400,'extensions'=>['mp3','m4a','aac','wav','ogg'],'mimes'=>['audio/mpeg','audio/mp4','audio/aac','audio/wav','audio/x-wav','audio/ogg']],
 'video'=>['max_bytes'=>1073741824,'extensions'=>['mp4','m4v','webm','mov'],'mimes'=>['video/mp4','video/webm','video/quicktime']],
 'document'=>['max_bytes'=>26214400,'extensions'=>['pdf'],'mimes'=>['application/pdf']],
]; }
function sf_admin_format_bytes($bytes): string { $n=max(0,(int)$bytes); foreach(['B','KB','MB','GB'] as $u){ if($n<1024||$u==='GB')return number_format($n,$u==='B'?0:1).' '.$u; $n/=1024;} return '0 B'; }
function sf_admin_detect_upload_type(string $mime,string $extension): ?string { foreach(sf_admin_upload_rules() as $type=>$rule) if(in_array($mime,$rule['mimes'],true)&&in_array($extension,$rule['extensions'],true)) return $type; return null; }
function sf_admin_handle_upload(string $field,string $requestedType='auto',string $usageKey='',string $title='',string $altText=''): array {
  if (empty($_FILES[$field]) || !is_array($_FILES[$field])) return ['ok'=>false,'message'=>'Choose a file to upload.'];
  $file=$_FILES[$field]; $error=(int)($file['error'] ?? UPLOAD_ERR_NO_FILE);
  if ($error!==UPLOAD_ERR_OK) return ['ok'=>false,'message'=>'Upload failed with error code '.$error.'.'];
  $tmp=(string)($file['tmp_name'] ?? ''); $size=(int)($file['size'] ?? 0); $original=basename((string)($file['name'] ?? 'upload'));
  if (!is_uploaded_file($tmp) || $size<1) return ['ok'=>false,'message'=>'The uploaded file is invalid.'];
  $extension=strtolower(pathinfo($original,PATHINFO_EXTENSION));
  $finfo=new finfo(FILEINFO_MIME_TYPE); $mime=strtolower((string)$finfo->file($tmp));
  $detected=sf_admin_detect_upload_type($mime,$extension);
  if (!$detected || ($requestedType!=='auto' && $requestedType!==$detected)) return ['ok'=>false,'message'=>'The file type is not allowed or does not match its contents.'];
  $rule=sf_admin_upload_rules()[$detected]; if ($size>(int)$rule['max_bytes']) return ['ok'=>false,'message'=>'File exceeds the '.sf_admin_format_bytes($rule['max_bytes']).' limit.'];
  $root=dirname(__DIR__); $relative='assets/'.$detected.'/uploads/'.date('Y/m'); $directory=$root.'/'.$relative;
  if (!is_dir($directory) && !mkdir($directory,0750,true) && !is_dir($directory)) return ['ok'=>false,'message'=>'Upload storage is unavailable.'];
  $filename=bin2hex(random_bytes(20)).'.'.$extension; $destination=$directory.'/'.$filename;
  if (!move_uploaded_file($tmp,$destination)) return ['ok'=>false,'message'=>'The file could not be stored.'];
  @chmod($destination,0640); $path=$relative.'/'.$filename;
  try {
    $pdo=sf_db(); if(!$pdo) throw new RuntimeException('Database unavailable.');
    $stmt=$pdo->prepare('INSERT INTO media_assets (title,file_path,file_type,alt_text,usage_key,original_filename,mime_type,file_size_bytes,storage_disk,created_at,updated_at) VALUES (?,?,?,?,?,?,?,?,?,NOW(),NOW())');
    $stmt->execute([$title!==''?$title:pathinfo($original,PATHINFO_FILENAME),$path,$detected,$altText!==''?$altText:null,$usageKey!==''?$usageKey:null,$original,$mime,$size,'local_assets']);
    return ['ok'=>true,'message'=>'Asset uploaded securely.','id'=>(int)$pdo->lastInsertId(),'path'=>$path,'type'=>$detected];
  } catch (Throwable $e) {
    @unlink($destination); error_log('Stonefellow upload registry failed: '.$e->getMessage());
    return ['ok'=>false,'message'=>'The file could not be registered.'];
  }
}
}
