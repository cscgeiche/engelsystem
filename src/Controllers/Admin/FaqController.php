<?php

namespace Engelsystem\Controllers\Admin;

use Engelsystem\Controllers\BaseController;
use Engelsystem\Controllers\HasUserNotifications;
use Engelsystem\Http\Redirector;
use Engelsystem\Http\Request;
use Engelsystem\Http\Response;
use Engelsystem\Models\Faq;
use Psr\Log\LoggerInterface;

class FaqController extends BaseController
{
    use HasUserNotifications;

    protected Redirector $redirect;

    /** @var array */
    protected array $permissions = [
        'faq.view',
        'faq.edit',
    ];

    public function __construct(
        protected LoggerInterface $log,
        protected Faq $faq,
        Redirector $redirector,
        protected Response $response
    ) {
        $this->redirect = $redirector;
    }

    public function edit(Request $request): Response
    {
        $faqId = $request->getAttribute('faq_id'); // optional

        $faq = $this->faq->find($faqId);

        return $this->showEdit($faq);
    }

    public function save(Request $request): Response
    {
        $faqId = $request->getAttribute('faq_id'); // optional

        /** @var Faq $faq */
        $faq = $this->faq->findOrNew($faqId);

        $data = $this->validate($request, [
            'question' => 'required',
            'text'     => 'required',
            'delete'   => 'optional|checked',
            'preview'  => 'optional|checked',
        ]);

        if (!is_null($data['delete'])) {
            $faq->delete();

            $this->log->info('Deleted faq "{question}"', ['question' => $faq->question]);

            $this->addNotification('faq.delete.success');

            return $this->redirect->to('/faq');
        }

        $faq->question = $data['question'];
        $faq->text = $data['text'];

        if (!is_null($data['preview'])) {
            return $this->showEdit($faq);
        }

        $faq->save();

        $this->log->info('Updated faq "{question}": {text}', ['question' => $faq->question, 'text' => $faq->text]);

        $this->addNotification('faq.edit.success');

        return $this->redirect->to('/faq#faq-' . $faq->id);
    }

    protected function showEdit(?Faq $faq): Response
    {
        return $this->response->withView(
            'pages/faq/edit.twig',
            ['faq' => $faq] + $this->getNotifications()
        );
    }
}
