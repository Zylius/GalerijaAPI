<?php

namespace Galerija\FrontendBundle\Controller;

use Galerija\APIBundle\Images\ImageStoreService;
use Lsw\ApiCallerBundle\Call\HttpGetJson;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class DefaultController extends Controller
{
    /**
     * Main page.
     *
     * @return Response
     */
    public function indexAction(Request $request)
    {
        return $this->render('GalerijaFrontendBundle:Default:index.html.twig');
    }

    /**
     * Request to authenticate with dropbox.
     *
     * @return RedirectResponse
     * @throws \Dropbox_Exception_RequestToken
     */
    public function dropboxAction()
    {
        if($this->get('galerija_api.images.storage')->getStorageTypeFromCookie() !== 'local') {
            $response = new RedirectResponse($this->get('router')->generate('galerija_frontend_homepage'));
            $response->headers->clearCookie(ImageStoreService::DROPBOX_STORAGE_NAME);

            return $response;
        }

        $dropboxApi = $this->get('galerija_api.dropbox.oauth');
        $this->get('session')->set('dropbox', ['request' => $dropboxApi->getRequestToken()]);

        return $this->redirect($dropboxApi->getAuthorizeUrl('http://awesome.dev' . $this->get('router')->generate('galerija_dropbox_complete')));
    }

    /**
     * Once authenticated, save the data.
     *
     * @param Request $request
     * @return RedirectResponse
     * @throws \Dropbox_Exception_RequestToken
     */
    public function dropboxCompleteAction(Request $request)
    {
        $session = $this->get('session');

        $dropboxApi = $this->get('galerija_api.dropbox.oauth');
        $dropboxApi->setToken($session->get('dropbox')['request']);
        $cookieCode = $this->get('galerija_api.images.storage')->saveStorage(
            [
                'request' => $session->get('dropbox')['request'],
                'access' => $dropboxApi->getAccessToken(),
                'uid' => $request->get('uid')
            ]
        );
        $response = new RedirectResponse($this->get('router')->generate('galerija_frontend_homepage'));
        $response->headers->setCookie(new Cookie(ImageStoreService::DROPBOX_STORAGE_NAME, $cookieCode, 0, '/', null, false, false));

        return $response;
    }
}
