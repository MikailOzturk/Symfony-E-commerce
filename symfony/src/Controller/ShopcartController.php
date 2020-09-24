<?php

namespace App\Controller;

use App\Entity\Shopcart;
use App\Form\ShopcartType;
use App\Repository\ShopcartRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/shopcart")
 */
class ShopcartController extends AbstractController
{
    /**
     * @Route("/", name="shopcart_index", methods="GET")
     */
    public function index(ShopcartRepository $shopcartRepository): Response
    {
        $cats = $this->categorytree();
        $cats[0] = '<ul id="menu-v" class="cat_menu">';

        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        $user = $this->getUser();

        $em = $this->getDoctrine()->getManager();
        $sql = "SELECT p.title,p.image,p.sprice,s.* 
                FROM shopcart s,product p 
                WHERE s.productid = p.id and userid= :userid";
        $statement = $em->getConnection()->prepare($sql);
        $statement->bindValue('userid',$user->getid());
        $statement->execute();
        $shopcart = $statement->fetchAll();

        return $this->render('shopcart/index.html.twig', [
            'shopcarts' => $shopcart,
            'cats' => $cats,
            ]);
    }

    /**
     * @Route("/new", name="shopcart_new", methods="GET|POST")
     */

    /**
     * @Route("/new", name="shopcart_new", methods="GET|POST")
     */
    public function new(Request $request): Response
    {
        $shopcart = new Shopcart();
        $form = $this->createForm(ShopcartType::class, $shopcart);
        $form->handleRequest($request);
        echo $submittedToken=$request->request->get('token');

        if($this->isCsrfTokenValid('add_item', $submittedToken)){
            if ($form->isSubmitted()) {
                $em = $this->getDoctrine()->getManager();
                //echo $request->request->get(quantity);

                $user=$this->getUser();

                $shopcart->setQuantity($form['quantity']->getData());
                $shopcart->setUserid($user->getid());

                $em->persist($shopcart);
                $em->flush();

                return $this->redirectToRoute('shopcart_index');
          }


        }

        return $this->render('shopcart/new.html.twig', [
            'shopcart' => $shopcart,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}", name="shopcart_show", methods="GET")
     */
    public function show(Shopcart $shopcart): Response
    {
        return $this->render('shopcart/show.html.twig', ['shopcart' => $shopcart]);
    }

    /**
     * @Route("/{id}/edit", name="shopcart_edit", methods="GET|POST")
     */
    public function edit(Request $request, Shopcart $shopcart): Response
    {
        $form = $this->createForm(ShopcartType::class, $shopcart);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->getDoctrine()->getManager()->flush();

            return $this->redirectToRoute('shopcart_edit', ['id' => $shopcart->getId()]);
        }

        return $this->render('shopcart/edit.html.twig', [
            'shopcart' => $shopcart,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/del/{id}", name="shopcart_del",methods="GET|POST")
     */
    public function del(Request $request, Shopcart $shopcart):Response
    {
        $em =$this->getDoctrine()->getManager();
        $em->remove($shopcart);
        $em->flush();
        return $this->redirectToRoute('shopcart_index');
        $this->addFlash('success',"Ürün sepetinizden silindi");
    }


    /**
     * @Route("/{id}", name="shopcart_delete", methods="GET|POST")
     */
    public function delete(Request $request, Shopcart $shopcart): Response
    {
        if ($this->isCsrfTokenValid('delete'.$shopcart->getId(), $request->request->get('_token'))) {
            $em = $this->getDoctrine()->getManager();
            $em->remove($shopcart);
            $em->flush();
        }

        return $this->redirectToRoute('shopcart_index');
    }

    public function categorytree($parent = 0, $user_tree_array = '')
    {
        if (!is_array($user_tree_array))
            $user_tree_array = array();

        $em = $this->getDoctrine()->getManager();
        $sql = "SELECT * FROM category WHERE status='True' AND parentid=" .$parent;
        $statement = $em->getConnection()->prepare($sql);
        //  $statement->bindValue('parentid',$parent);
        $statement->execute();
        $result = $statement->fetchAll();
        if (count($result) > 0) {
            $user_tree_array[] = "<ul>";
            foreach ($result as $row) {
                $user_tree_array[] = "<li ><a href='/category/" . $row['id'] . "'>" . $row['title'] . "</a>";
                $user_tree_array = $this->categorytree($row['id'], $user_tree_array);
            }
            $user_tree_array[] = "</li></ul>";

        }

        return $user_tree_array;
    }
}
