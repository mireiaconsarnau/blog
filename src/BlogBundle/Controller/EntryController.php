<?php

namespace BlogBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use BlogBundle\Entity\Entry;
use BlogBundle\Form\EntryType;


class EntryController extends Controller
{
	private $session;
	
	public function __construct() {
		$this->session=new Session();
	}
        
//	public function indexAction(Request $request, $page){		
//		$em = $this->getDoctrine()->getEntityManager();
//		$entry_repo=$em->getRepository("BlogBundle:Entry");
//                $entries=$entry_repo->findAll();
//		$category_repo=$em->getRepository("BlogBundle:Category");
//		$categories=$category_repo->findAll();
//		
////		$pageSize = 5;
////		$entries=$entry_repo->getPaginateEntries($pageSize,$page);
////		
////		$totalItems = count($entries);
////		$pagesCount = ceil($totalItems/$pageSize);
////		
////		
////		return $this->render("BlogBundle:Entry:index.html.twig",array(
////			"entries" => $entries,
////			"categories" => $categories,
////			"totalItems" => $totalItems,
////			"pagesCount" => $pagesCount,
////			"page" => $page,
////			"page_m" => $page
////		));
//
//	}
        public function indexAction(Request $request){		
		$em = $this->getDoctrine()->getEntityManager();
		$entry_repo=$em->getRepository("BlogBundle:Entry");
                $entries=$entry_repo->findAll();
                $category_repo=$em->getRepository("BlogBundle:Category");
		$categories=$category_repo->findAll();
                
                return $this->render("BlogBundle:Entry:index.html.twig",array(
			"entries" => $entries,
                        "categories" => $categories,

		));
	}


	public function addAction(Request $request){
		$entry = new Entry();
		$form = $this->createForm(EntryType::class,$entry);
		
		$form->handleRequest($request);
		
		if($form->isSubmitted()){
			if($form->isValid()){
				
				$em = $this->getDoctrine()->getEntityManager();
				$entry_repo=$em->getRepository("BlogBundle:Entry");
				$category_repo=$em->getRepository("BlogBundle:Category");
				
				$entry = new Entry();
				$entry->setTitle($form->get("title")->getData());
				$entry->setContent($form->get("content")->getData());
				$entry->setStatus($form->get("status")->getData());
				
				// upload file
				$file=$form["image"]->getData();
				
				if(!empty($file) && $file!=null){
					$ext=$file->guessExtension();
					$file_name=time().".".$ext;
					$file->move("uploads",$file_name);

					$entry->setImage($file_name);
				}else{
					$entry->setImage(null);
				}
				
				$category = $category_repo->find($form->get("category")->getData());
				$entry->setCategory($category);
				
				$user=$this->getUser();
				$entry->setUser($user);
				
				$em->persist($entry);
				$flush=$em->flush();
				
				$entry_repo->saveEntryTags(
						$form->get("tags")->getData(),
						$form->get("title")->getData(),
						$category,
						$user
						);
				
				if($flush==null){
					$status = "Entrada creada correctament.";
				}else{
					$status ="Alguna cosa ha fallat";
				}
				
			}else{
				$status = "Formulari no vàlid. Entrada no creada.";
			}
			
			$this->session->getFlashBag()->add("status", $status);
			return $this->redirectToRoute("blog_homepage");
		}
		
		
                return $this->render("BlogBundle:Entry:add.html.twig",array(
                        "form" => $form->createView()
                ));
	}
	
	public function deleteAction($id){
		$em = $this->getDoctrine()->getEntityManager();
		$entry_repo=$em->getRepository("BlogBundle:Entry");
		$entry_tag_repo=$em->getRepository("BlogBundle:EntryTag");
		
		$entry=$entry_repo->find($id);
		
		$entry_tags=$entry_tag_repo->findBy(array("entry"=>$entry));
		
		foreach($entry_tags as $et){
			if(is_object($et)){
				$em->remove($et);
				$em->flush();
			}
		}
		
		if(is_object($entry)){
			$em->remove($entry);
			$em->flush();
		}
		
		return $this->redirectToRoute("blog_homepage");
	}
	
	public function editAction(Request $request, $id){
		$em = $this->getDoctrine()->getEntityManager();
		$entry_repo = $em->getRepository("BlogBundle:Entry");
		$category_repo = $em->getRepository("BlogBundle:Category");
		
		$entry=$entry_repo->find($id);
		$entry_image=$entry->getImage();
		
		$tags="";
		foreach($entry->getEntryTag() as $entryTag){
			$tags.=$entryTag->getTag()->getName().",";
		}
		
		$form = $this->createForm(EntryType::class, $entry);
		
		$form->handleRequest($request);
		
		if($form->isSubmitted()){
			if($form->isValid()){
				
				/*
					$entry->setTitle($form->get("title")->getData());
					$entry->setContent($form->get("content")->getData());
					$entry->setStatus($form->get("status")->getData());
				 */
				
				// upload file
				$file=$form["image"]->getData();
				
				if(!empty($file) && $file!=null){
					$ext=$file->guessExtension();
					$file_name=time().".".$ext;
					$file->move("uploads",$file_name);

					$entry->setImage($file_name);
				}else{
					$entry->setImage($entry_image);
				}

				$category = $category_repo->find($form->get("category")->getData());
				$entry->setCategory($category);
				
				$user=$this->getUser();
				$entry->setUser($user);
				
				$em->persist($entry);
				$flush=$em->flush();
				
				
				$entry_tag_repo=$em->getRepository("BlogBundle:EntryTag");
				$entry_tags=$entry_tag_repo->findBy(array("entry"=>$entry));

				foreach($entry_tags as $et){
					if(is_object($et)){
						$em->remove($et);
						$em->flush();
					}
				}
				
				$entry_repo->saveEntryTags(
						$form->get("tags")->getData(),
						$form->get("title")->getData(),
						$category,
						$user
						);
				
				if($flush==null){
					$status = "Entrada creada correctament";
				}else{
					$status = "Alguna cosa ha fallat";
				}
				
			}else{
				$status = "Formulari no vàlid";
			}
			
			$this->session->getFlashBag()->add("status", $status);
			return $this->redirectToRoute("blog_homepage");
		}
		
		return $this->render("BlogBundle:Entry:edit.html.twig",array(
			"form" => $form->createView(),
			"entry" => $entry,
			"tags" => $tags
		));
	}
	
}