<?php
  /*
  +--------------------------------------------------------------------------
  |   Application's User Model Class
  |   ========================================
  |   by Cristian R. Arroyo
  |   (c) 2007-2008 VivaServer
  |   http://www.vivaserver.com.ar
  |   Email: cristian.arroyo@vivaserver.com.ar
  +--------------------------------------------------------------------------
  |   THIS IS NOT FREE SOFTWARE!
  +--------------------------------------------------------------------------
  */

  class usuario extends model {
    protected $table_name;   // set in $conf['tb_usuarios']
    protected $attributes = array(
      'group_id',
      'user_name',
      'user_password',
      'first_name',
      'last_name',
      'is_disabled'
    );
    public $data = array();
    public $id = FALSE;

    function __construct($id=FALSE) {
      $this->table_name = $GLOBALS['conf']['tb_usuarios'];
      parent::__construct($id);
    }

    function create($array) {
      global $auth;
      // encode password before creating
      $array['f_user_password'] = $auth->encoder->Encrypt_Text($array['f_user_password']);
      return parent::create($array);
    }

    function update($array) {
      global $auth, $site;
      if ($array['f_user_password']) {
        // encode password before updating
        $array['f_user_password'] = $auth->encoder->Encrypt_Text($array['f_user_password']);
      }
      if (parent::update($array)) {
        if ($site->params['f_group_id'] == $this->conf['admin_group_id'] &&  $this->data['group_id'] != $this->conf['admin_group_id']) {
          // user group changed to Admin. remove any previous enabled secretaria
          $sql  = "delete ";
          $sql .= "from ";
          $sql .= " {$this->conf['tb_usuarios_secretarias']} ";
          $sql .= "where ";
          $sql .= " user_id='{$this->id}' ";
          return $this->db->query($sql);
        }
        else {
          return TRUE;
        }
      }
      else {
        return FALSE;
      }
    }

    function find($id) {
      $row = parent::find($id);
      if ($row) {
        $row['user_password'] = $GLOBALS['auth']->encoder->Decrypt_Text($row['user_password']);
      }
      return $row;
    }

    function find_all($conditionvs=array(), $order=FALSE, $limit=FALSE, $offset=FALSE) {
      $sql  = "select ";
      $sql .= " u.*, ";
      $sql .= " to_char(u.last_login_at,'DD/MM/YYYY') as last_login, ";
      $sql .= " g.nombre as grupo ";
      $sql .= "from ";
      $sql .= " {$this->table_name} as u ";
      $sql .= "join ";
      $sql .= " {$this->conf['tb_grupos']} as g ";
      $sql .= "on ";
      $sql .= " u.group_id=g.id ";
      $sql .= $this->build_where($conditions);
      $sql .= "order by ";
      $sql .= " u.last_name, u.first_name ";
      return $this->db->get_results($sql,ARRAY_A);
    }

    function is_admin() {
      if ($this->data['group_id']==$this->conf['admin_group_id']) {
        return TRUE;
      }
      else {
        return FALSE;
      }
    }

    //
    // returns enabled/disabled secretarias for current user
    //
    function secretarias($opt='enabled') {
      $sql  = "select ";
      $sql .= " s.id, ";
      $sql .= " substring(nombre from 5) as nombre ";
      $sql .= "from ";
      $sql .= " {$this->conf['tb_secretarias']} as s ";
      $sql .= "where ";
      $sql .= " s.id ";
      $sql .= ($opt=='enabled' ? 'in (' : 'not in (');
      $sql .= " select ";
      $sql .= "  secretaria_id ";
      $sql .= " from ";
      $sql .= "  {$this->conf['tb_usuarios_secretarias']} ";
      $sql .= " where ";
      $sql .= "  user_id='{$this->id}' ";
      $sql .= " ) ";
      $sql .= "order by ";
      $sql .= " s.nombre ";
      return $this->db->get_results($sql,ARRAY_A);
    }
  }

  class usuario_secretaria extends model {
    protected $table_name;   // set in $conf['tb_usuarios_secretarias']
    protected $attributes = array(
      'user_id',
      'secretaria_id'
    );
    public $data = array();
    public $id = FALSE;

    function __construct($id=FALSE) {
      $this->table_name = $GLOBALS['conf']['tb_usuarios_secretarias'];
      parent::__construct($id);
    }
  }
?>
