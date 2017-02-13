<?php
namespace App\Controller\Mobilizer;

use App\Controller\AppController;
use Cake\Event\Event;
use Cake\I18n\FrozenTime;
use Cake\Utility\Text;

/**
 * Events Controller
 *
 * @property \App\Model\Table\EventsTable $Events
 */
class EventsController extends AppController {

    public function initialize() {

        parent::initialize();
        $this->loadComponent('RequestHandler');
        $this->loadModel( 'Events' );
    }

    public function beforeFilter( Event $event ) {
        parent::beforeFilter( $event );
    }

    /**
     * Index method
     *
     * @return \Cake\Network\Response|null
     */
    public function index() {
        $events = $this->paginate( $this->Events , [ 'contain' => [ 'Users' ] ] );

        $this->set(compact('events'));
        $this->set('_serialize', ['events']);
    }

    /**
     * View method
     *
     * @param string|null $id Event id.
     * @return \Cake\Network\Response|null
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function view( $id = null )
    {
        $event = $this->Events->get($id, [
            'contain' => [ 'Users', 'Media' ]
        ]);

        $this->set('event', $event);
        $this->set('_serialize', ['event']);
    }

    /**
     * Add method
     *
     * @return \Cake\Network\Response|void Redirects on successful add, renders view otherwise.
     */
    public function add() {

        $event = $this->Events->newEntity();

        if ($this->request->is('post')) {

            $this->request->data[ 'event_date' ] = FrozenTime::createFromFormat(
                'F j, Y',
                $this->request->data[ 'event_date' ],
                'America/New_York'
            );

            log_data( 'request' , $this->request );

            foreach( $this->request->data[ 'media' ] as &$media ) {
                //$media[ 'filename' ] = $media[ 'filename' ];
                unset( $media[ 'file' ] );
            }

            log_data( 'request' , $this->request );

            $event = $this->Events->patchEntity($event, $this->request->data);

            log_data( 'event' , $event );

            $event->user_id = 1;

            if ($this->Events->save($event)) {
                $this->Flash->success(__('The event has been saved.'));

                return $this->redirect(['action' => 'index']);
            } else {
                $this->Flash->error(__('The event could not be saved. Please, try again.'));
            }
        }

        $this->set(compact('event'));
        $this->set('_serialize', ['event']);
    }

    public function uploadMedia() {
        if ( $this->request->is( 'ajax' ) ) {

            $this->RequestHandler->renderAs( $this , 'json' );
            $this->response->type('application/json');

            log_data( 'post' , $this->request );
            log_data( 'files' , $_FILES );

            if( !$this->request->data[ 'media' ][ 'error' ] ) {
                $timestamp = new FrozenTime();
                $uuid = Text::uuid();
                $filename = $timestamp->toUnixString() . '-' . $uuid . '.jpg';
                move_uploaded_file( $this->request->data[ 'media' ][ 'tmp_name' ] , WWW_ROOT . 'media' . DS . $filename );
            }

            $response = [
                'success' => true,
                'message' => 'Testing out this interface.',
                'title' => $this->request->data[ 'title' ],
                'description' => $this->request->data[ 'description' ],
                'media' => $filename
            ];

            $this->set( compact( 'response' ) );
            $this->set( '_serialize', ['response' ]);
        }
    }

    /**
     * Edit method
     *
     * @param string|null $id Event id.
     * @return \Cake\Network\Response|void Redirects on successful edit, renders view otherwise.
     * @throws \Cake\Network\Exception\NotFoundException When record not found.
     */
    public function edit($id = null)
    {
        $event = $this->Events->get($id, [
            'contain' => []
        ]);
        if ($this->request->is(['patch', 'post', 'put'])) {
            $event = $this->Events->patchEntity($event, $this->request->data);
            if ($this->Events->save($event)) {
                $this->Flash->success(__('The event has been saved.'));

                return $this->redirect(['action' => 'index']);
            } else {
                $this->Flash->error(__('The event could not be saved. Please, try again.'));
            }
        }
        $this->set(compact('event'));
        $this->set('_serialize', ['event']);
    }

    /**
     * Delete method
     *
     * @param string|null $id Event id.
     * @return \Cake\Network\Response|null Redirects to index.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function delete($id = null)
    {
        $this->request->allowMethod(['post', 'delete']);
        $event = $this->Events->get($id);
        if ($this->Events->delete($event)) {
            $this->Flash->success(__('The event has been deleted.'));
        } else {
            $this->Flash->error(__('The event could not be deleted. Please, try again.'));
        }

        return $this->redirect(['action' => 'index']);
    }
}