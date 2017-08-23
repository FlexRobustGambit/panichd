<?php

namespace Kordy\Ticketit\Traits;

use Kordy\Ticketit\Models\Attachment;
use Kordy\Ticketit\Models\Setting;
use Illuminate\Support\Str;
use Log;
use Symfony\Component\HttpFoundation\File\UploadedFile;

trait Attachments
{
	/**
     * Saves form attached files in name="attachments[]"
     *
     * @param Request $request
	 * @param $ticket instance of Kordy\Ticketit\Models\Ticket
	 * @param $comment instance of Kordy\Ticketit\Models\Comment
     *
     * @return string
	 * @return bool
     */
    protected function saveAttachments($request, $ticket, $comment = false)
    {
		if (!$request->attachments){			
			return false;
		}
		
		$bytes = $ticket->allAttachments()->sum('bytes');
		$num = $ticket->allAttachments()->count();
		
		$new_bytes = 0;
		
		foreach ($request->attachments as $uploadedFile) {
            /** @var UploadedFile $uploadedFile */
            if (is_null($uploadedFile)) {
                // No files attached
                return trans('ticketit::lang.ticket-error-not-valid-file');
            }

            if (!$uploadedFile instanceof UploadedFile) {
                Log::error('File object expected, given: '.print_r($uploadedFile, true));
                return trans('ticketit::lang.ticket-error-not-valid-object', ['name'=>print_r($uploadedFile, true)]);
            }
			
			$original_filename = $uploadedFile->getClientOriginalName() ?: '';
			
			// Denied uploads block process
			if (is_array($request->block_file_names) and in_array($original_filename, $request->block_file_names)){
				continue;
			}			
			
			$new_bytes = $bytes + $uploadedFile->getSize();
			
			if ($new_bytes/1024/1024 > Setting::grab('attachments_ticket_max_size')){
				
				return trans('ticketit::lang.ticket-error-max-size-reached', [
					'name' => $original_filename,
					'available_MB' => round(Setting::grab('attachments_ticket_max_size')-$bytes/1024/1024)
				]);
			}			
			$bytes = $new_bytes;						
			
			if ($num + 1 > Setting::grab('attachments_ticket_max_files_num')){
				return trans('ticketit::lang.ticket-error-max-attachments-count-reached', [
					'name' => $original_filename,
					'max_count'=>Setting::grab('attachments_ticket_max_files_num')
				]);
			}			
			$num++;

            $attachments_path = Setting::grab('attachments_path');
            $file_name = auth()->user()->id.'_'.$ticket->id.'_'.($comment ? $comment->id : '').md5(Str::random().$uploadedFile->getClientOriginalName());
            $file_directory = storage_path($attachments_path);

            $attachment = new Attachment();
            $attachment->ticket_id = $ticket->id;
			if ($comment){
				$attachment->comment_id = $comment->id;
				$attachment->uploaded_by_id = $comment->user_id;
			}else{
				$attachment->uploaded_by_id = $ticket->user_id;
			}            
            $attachment->original_filename = $attachment->new_filename = $original_filename;
            $attachment->bytes = $uploadedFile->getSize();
            $attachment->mimetype = $uploadedFile->getMimeType() ?: '';
            $attachment->file_path = $file_directory.DIRECTORY_SEPARATOR.$file_name;
            $attachment->save();

            // Should be called when you no need anything from this file, otherwise it fails with Exception that file does not exists (old path being used)
            $uploadedFile->move(storage_path($attachments_path), $file_name);
        }
		
		return false;
    }
	
	/**
	 * Updates new_filename and description for any attachment
	*/
	protected function updateAttachmentFields($request, $attachments)
	{
		foreach($attachments as $att){
			$save = false;
			
			if ($request->has('attachment_'.$att->id.'_new_filename')){
				$new_filename = $request->input('attachment_'.$att->id.'_new_filename');
				
				if ($new_filename != "" and $new_filename != $att->new_filename){
					$att->new_filename = $new_filename;
					$save = true;					
				}
			}
			
			if ($request->has('attachment_'.$att->id.'_description')){
				$description = $request->input('attachment_'.$att->id.'_description');
				if ($description != "" and $description != $att->description){
					$att->description = $description;
					$save = true;
				}
			}
			
			if ($save) $att->save();
		}
	}
	
	/**
     * Destroys related attachments of $ticket or $comment
     *
     * @param $ticket instance of Kordy\Ticketit\Models\Ticket
	 * @param $comment instance of Kordy\Ticketit\Models\Comment
     *
     * @return string
	 * @return bool
     */
    protected function destroyAttachments($ticket, $comment = false)
    {
		if ($comment){
			$attachments = Attachment::where('comment_id',$comment->id)->get();
		}else{
			$attachments = Attachment::where('ticket_id',$ticket->id)->get();
		}
		
		return $this->destroyAttachmentLoop($attachments);
	}
	
	
	protected function destroyAttachmentIds($a_id)
	{
		$attachments = Attachment::whereIn('id', $a_id)->get();		
		
		return $this->destroyAttachmentLoop($attachments);
	}
	
	/**
	 * Iterates through selected $attachments as instances of Attachment model
	 *
	 * @param $ticket instance of Kordy\Ticketit\Models\Ticket
	 *
     * @return string
	 * @return bool
	*/
	protected function destroyAttachmentLoop($attachments)
	{
		$delete_errors = [];
				
		foreach ($attachments as $attachment){			
			$single = $this->destroyAttachedElement($attachment);
			if ($single) $delete_errors[] = $single;
		}
		
		if ($delete_errors){
			return trans('ticketit::lang.ticket-error-delete-files').trans('ticketit::lang.colon').implode('. ', $delete_errors);
		}else{
			return false;
		}
	}
	
	/**
	 * Destroy for single attachment model instance
	*/
	protected function destroyAttachedElement($attachment)
	{
		if(!\File::exists($attachment->file_path)){
			return trans('ticketit::lang.ticket-error-file-not-found', ['name'=>$attachment->original_filename]);
		}else{
			\File::delete($attachment->file_path);
			
			if(\File::exists($attachment->file_path)){
				return trans('ticketit::lang.ticket-error-file-not-deleted', ['name'=>$attachment->original_filename]);
			}else{
				$attachment->delete();
				return false;
			}
		}
	}
	

}
