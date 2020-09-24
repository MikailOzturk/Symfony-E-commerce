<?php

namespace App\Controller;

use App\Entity\OrderDetail;
use App\Entity\Orders;
use App\Form\OrdersType;
use App\Repository\OrderDetailRepository;
use App\Repository\OrdersRepository;
use App\Repository\ShopcartRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/orders")
 */
class OrdersController extends AbstractController
{
    /**
     * @Route("/", name="orders_index", methods="GET")
     */
    public function index(OrdersRepository $ordersRepository): Response
    {
        $cats = $this->categorytree();
        $cats[0] = '<ul id="menu-v" class="cat_menu">';
        return $this->render('orders/index.html.twig', ['orders' => $ordersRepository->findAll(),
            'cats' => $cats,
            ]);
    }

    /**
     * @Route("/new", name="orders_new", methods="GET|POST")
     */
    public function new(Request $request, ShopcartRepository $shopcartRepository): Response
    {
        $cats = $this->categorytree();
        $cats[0] = '<ul id="menu-v" class="cat_menu">';

        $orders = new Orders();
        $form = $this->createForm(OrdersType::class, $orders);
        $form->handleRequest($request);

        $user = $this->getUser();
        $userid= $user->getid();

        $total =$shopcartRepository->getUserShopCartTotal($userid);
        $submittedToken = $request->request->get('token');
        //$em1 = $this->getDoctrine()->getManager();



       // if($this->isCsrfTokenValid('form-order',$submittedToken)){

            if ($form->isSubmitted()) {
                //kredi kartı bilgilerini ilgili bankaya gönder
                //onay gelirse kaydetmeye devam et gelmezse hata gönder
                $em = $this->getDoctrine()->getManager();

                $orders->setUserid($userid);
                $orders->setAmount($total);
                $orders->setStatus('New');

                $em->persist($orders);
                $em->flush();

                $orderid=$orders->getId();
                $shopcart=$shopcartRepository->getUserShopcart($userid);

                foreach ($shopcart as $item){
                    $orderdetail= new OrderDetail();
                    $orderdetail->setOrderid($orderid);
                    $orderdetail->setUserid($user->getid());
                    $orderdetail->setProductid($item['productid']);
                    $orderdetail->setPrice($item['sprice']);
                    $orderdetail->setQuantity($item['quantity']);
                    $orderdetail->setAmount($item['total']);
                    $orderdetail->setName($item['title']);
                    $orderdetail->setStatus("Ordered");
                    $em->persist($orderdetail);
                    $em->flush();

                }


                $em = $this->getDoctrine()->getManager();
                $query=$em->createQuery('
                    DELETE FROM App\Entity\Shopcart s Where s.userid=:userid
                ')->setParameter('userid',$userid);
                $query->execute();
                $this->addFlash('success','Siparişiniz başarıyla gerçekleşmiştir.Teşekkür ederiz.');

                return $this->redirectToRoute('orders_index');
            }

        //}
        return $this->render('orders/new.html.twig', [
            'order' => $orders,
            'cats' => $cats,
            'form' => $form->createView(),
            'total' => $total,
            //'shopcarts' => $shopcart,
        ]);
    }
    /**
     * @Route("/{id}", name="orders_show", methods="GET")
     */
    public function show(Orders $order,OrdersRepository $ordersRepository, OrderDetailRepository $orderDetailRepository): Response
    {
        $cats = $this->categorytree();
        $cats[0] = '<ul id="menu-v" class="cat_menu">';
        $user = $this->getUser();
        $userid=$user->getId();
        $orderid=$order->getId();

        $orderdetail=$orderDetailRepository->findBy(
            ['orderid' => $orderid]
        );

        return $this->render('orders/show.html.twig', [
            'order' => $order,
            'orderdetail' => $orderdetail,
            'cats' => $cats,

            ]);
    }

    /**
     * @Route("/{id}/edit", name="orders_edit", methods="GET|POST")
     */
    public function edit(Request $request, Orders $order): Response
    {
        $form = $this->createForm(OrdersType::class, $order);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->getDoctrine()->getManager()->flush();

            return $this->redirectToRoute('orders_edit', ['id' => $order->getId()]);
        }

        return $this->render('orders/edit.html.twig', [
            'order' => $order,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}", name="orders_delete", methods="DELETE")
     */
    public function delete(Request $request, Orders $order): Response
    {
        if ($this->isCsrfTokenValid('delete'.$order->getId(), $request->request->get('_token'))) {
            $em = $this->getDoctrine()->getManager();
            $em->remove($order);
            $em->flush();
        }

        return $this->redirectToRoute('orders_index');
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
