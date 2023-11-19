<?php

namespace App\Livewire;
use Illuminate\Support\Collection;
use App\Models\Post;
use Livewire\Component;
use Livewire\Attributes\On; 
use Livewire\WithFileUploads;

use Livewire\Attributes\Rule;
use App\Models\Community;
use Illuminate\Support\Facades\Storage;

class Feed extends Component
{
    public $preferencia = 'all';
       
    use WithFileUploads;
    public $loadedPosts = 10;

    #[Rule('required_without_all:image,video|max:100')]
    public $conteudo;

    #[Rule('required_without_all:conteudo,video|max:10240')]
    public $image;

    #[Rule('required_without_all:conteudo,image')]
    public $video;
  
  public function allPosts() {
    $this->preferencia = 'all';
  }

  public function myCommunities() {
    $this->preferencia = 'minhasComunidades';
  }

    public function like(int $postId){
        $user = auth()->user();
        $post = Post::findOrFail($postId);

     
        if ($user->likes()->where('post_id', $post->id)->exists()) {
        
            $this->dispatch('message', title: 'Post já foi curtido', type: 'warning');
            
    
       
    } else {
        $user->likes()->attach($post);
        $this->dispatch('message', title: 'Post curtido com sucesso', type: 'success');
        $post->likes = $post->likedBy->count();
        $post->save();
    }
    }

    public function dislike(int $postId) {
        $user = auth()->user();
        $post = Post::findOrFail($postId);

      
         if($user->likes->contains($post)) {

       
         
            $user->likes()->detach($post);

            $this->dispatch('message', title: 'Post descurtido com sucesso', type: 'success');
      

   
        $post->likes = $post->likedBy->count();
        $post->save(); 
    }
        else  {
            $this->dispatch('message', title: 'Post já foi descurtido', type: 'warning');
     }
 
    }



   
    public function post()
    {
   $this->validate();


    
        $user = auth()->user()->id;
        $post = new Post;
        $post->conteudo = $this->conteudo;
        $post->user_id = $user;

        if ($this->video) {
            $videoPath = $this->video->store('uploads', 'public');
            $post->video = $videoPath;
        }
    
        if ($this->image) {
            $imagePath = $this->image->store('uploads', 'public');
            $post->image = $imagePath;
        }
    

   
 
        
    
        $post->save();
        
        $this->conteudo = '';
        $this->image = null;
        $this->video = null;
        $this->dispatch('post-created');
        $this->dispatch('message', title: 'Post criado com sucesso.', type: 'success');
  
    }
    public function loadMorePosts()
{


    $this->loadedPosts += 10; 

  
}

public function deletePost($id) {

 
    $post = Post::findOrFail($id);

    

    if($post->user_id === auth()->user()->id) {
        
 

    $imagePath = $post->image;

    $videoPath = $post->video;
    if ($imagePath) {
        
        Storage::disk('public')->delete($imagePath);
    }

    if($videoPath) {
        Storage::disk('public')->delete($videoPath);
    }
    $post->delete();
    $this->dispatch('message', title: 'Post deletado com sucesso.', type: 'success');
}
}

#[On('post-created')] 
public function refreshPosts()
{
    $this->render();
 
}



    public function render(){
        $user = auth()->user();
        if($this->preferencia === 'all') {

       
        $posts = Post::latest()->get();
       } else {

$postsFromMemberCommunities = $user->communitys->flatMap(function ($community) {
    return $community->posts;
});

$posts = $postsFromMemberCommunities->sortByDesc('created_at');


}
        $quantityOfPosts = Post::count();

     
        $user->load('likes');

        return view('livewire.feed', compact('posts', 'user',));
    }
}