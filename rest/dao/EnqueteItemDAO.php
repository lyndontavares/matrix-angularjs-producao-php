<?php
class EnqueteItemDAO 
{
  
  private $_select_dev = 
          "SELECT 
              i.id, i.enquete_id, i.nome, p.id pergunta_id, p.nome pergunta_nome, i.resposta, i.avaliado 
           FROM 
               tab_enquete_item i 
                  inner join tab_pergunta p on ( i.pergunta = p.id )";
    
  private $_select_item = "SELECT * FROM tab_enquete_item";
  
  private $_insert = "INSERT INTO tab_enquete_item( enquete_id, nome, pergunta, resposta, avaliado  ) VALUES( :enquete_id, :nome, :pergunta, :resposta, :avaliado  )";
  
  private $_update = "UPDATE tab_enquete_item SET resposta = :resposta  WHERE id = :id";
  
  private $_delete = "DELETE FROM tab_enquete_item WHERE id = :id";

  private $_deleteAll = "DELETE FROM tab_enquete_item WHERE enquete_id = :enquete_id";
  
  private function getDBConn() 
  {
    $dbhost="localhost";
    $dbuser='root';
    $dbpass="Data.01zero";
    //$dbpass="1234";
    $dbname="enquete_dev3";
    $dbh = new PDO("mysql:host=$dbhost;dbname=$dbname", $dbuser, $dbpass);  
    $dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    return $dbh;
  }

  
  public function getByDesenv($vo) 
  {
      
    //var_dump( $vo ); var_dump( '<<<' );   
      
    $vo = json_decode($vo); //var_dump( $vo ); 
      
    $sql = $this->_select_dev . " WHERE i.nome = :avaliador and i.avaliado = :avaliado ";
    
    try {
      $db = $this->getDBConn();
      $stmt = $db->prepare($sql);  
      $stmt->bindParam("avaliador", $vo->avaliador);  
      $stmt->bindParam("avaliado", $vo->avaliado);  
      
      
      //var_dump(  $stmt);
      
      $stmt->execute();
      $result = $stmt->fetchAll(PDO::FETCH_OBJ);
      $db = null;
      
      echo '{"records":'.json_encode($result).'}';
    } catch(PDOException $e) {
      echo '{"error":{"text":'. $e->getMessage() .'}}'; 
    }
  }
  

   public function getCount($vo) 
  {
       
    $vo = json_decode($vo);
       
    $sql = "select count(*) total from tab_enquete_item where resposta <> 0 and enquete_id=1 and nome = :avaliador and avaliado = :avaliado ";
    
    try {
      $db = $this->getDBConn();
      $stmt = $db->prepare($sql);  
      $stmt->bindParam("avaliador", $vo->avaliador);  
      $stmt->bindParam("avaliado", $vo->avaliado);  
      $stmt->execute();
      $result = $stmt->fetchAll(PDO::FETCH_OBJ);
      $db = null;
      echo '{"records":'.json_encode($result).'}';
    } catch(PDOException $e) {
      echo '{"error":{"text":'. $e->getMessage() .'}}'; 
    }
  }
  
  
   public function getGraficoExpertise($vo) 
  {
       
    $vo = json_decode($vo);
       
    $sql = "select 
             concat( concat( concat(d.time,' :total ['),count(i.resposta)) , ']') as 'key', count(i.resposta) as y
            from 
              tab_enquete_item i 
                inner join tab_expertise e on ( e.id = i.resposta )
                inner join tab_desenv d on ( i.nome = d.nome )
                inner join tab_pergunta p on ( i.pergunta = p.id )
            where 
              i.resposta = :id and i.pergunta = :mod
            group by 
              d.time, e.id ; ";
    
    try {
      $db = $this->getDBConn();
      $stmt = $db->prepare($sql);  
      $stmt->bindParam("mod", $vo->mod);  
      $stmt->bindParam("id", $vo->id);  
      $stmt->execute();
      $result = $stmt->fetchAll(PDO::FETCH_OBJ);
      $db = null;
      echo   '{"records":'.json_encode($result).'}' ;
    } catch(PDOException $e) {
      echo '{"error":{"text":'. $e->getMessage() .'}}'; 
    }
  }
    
   public function getGraficoPercentual() 
  {
       
    $sql = "select
            concat( 
            concat( d.time,' ') ,
            concat(  
            ( select count(*) from tab_enquete_item i inner join tab_desenv dd on ( dd.nome = i.nome ) where dd.time = d.time and i.resposta > 0) ,
            concat( ' de ',
             (select count(*) from tab_enquete_item i inner join tab_desenv dd on ( dd.nome = i.nome ) where dd.time = d.time )
            ) 
            )
            ) as label ,
            ( ( select count(*) from tab_enquete_item i inner join tab_desenv dd on ( dd.nome = i.nome ) where dd.time = d.time and i.resposta > 0) /
            ( select count(*) from tab_enquete_item i inner join tab_desenv dd on ( dd.nome = i.nome ) where dd.time = d.time) * 100 ) as value
            from tab_desenv d group by d.time ;";

    try {
      $db = $this->getDBConn();
      $stmt = $db->prepare($sql);  
      $stmt->execute();
      $result = $stmt->fetchAll(PDO::FETCH_OBJ);
      $db = null;
      echo   '{"records":'.json_encode($result).'}' ;
    } catch(PDOException $e) {
      echo '{"error":{"text":'. $e->getMessage() .'}}'; 
    }
  }

   public function getGraficoResultado($vo) 
  {
       
    $sql = "select 

        s.nome,
        s.avaliacao_pessoal,
        case 
          when s.avalicao_grupo  = 0 then 'N/A'
          when s.avalicao_grupo > 0.0 and s.avalicao_grupo	< 1.5 then 'Novato'
          when s.avalicao_grupo >= 1.5 and s.avalicao_grupo	< 2.5  then 'Aprendiz'
          when s.avalicao_grupo >= 2.5 and s.avalicao_grupo	< 3.5  then 'Praticante'
          when s.avalicao_grupo >= 3.5 and s.avalicao_grupo	< 4.5  then 'Profissional'
          when s.avalicao_grupo >= 4.5 and s.avalicao_grupo	< 6.6  then 'Professor'
          when s.avalicao_grupo >= 5.5 and s.avalicao_grupo	< 6.5  then 'Líder'
          when s.avalicao_grupo >= 6.5 then 'Mestre'
        end avaliacao_grupo 

      from	

      (

      select
        p.nome, 
        r.nome avaliacao_pessoal,

        ( 

        select
         avg( i.resposta )  
        from
         tab_pergunta p , tab_enquete_item i , tab_expertise r
        where
         i.pergunta = p.id and
         i.resposta = r.id and
         i.avaliado = :nome and
         i.pergunta = ii.pergunta
        group by 
         p.nome   


         ) avalicao_grupo	

      from
        tab_enquete_item ii , tab_pergunta p , tab_expertise r
      where
        ii.pergunta = p.id and
        ii.resposta = r.id and
        ii.nome = :nome and
        ii.avaliado = :nome

      ) s ";

    try {
      $db = $this->getDBConn();
      $stmt = $db->prepare($sql);  
      $stmt->bindParam("nome", $vo->nome); 
      $stmt->execute();
      $result = $stmt->fetchAll(PDO::FETCH_OBJ);
      $db = null;
      echo   '{"records":'.json_encode($result).'}' ;
    } catch(PDOException $e) {
      echo '{"error":{"text":'. $e->getMessage() .'}}'; 
    }
  }

  
   public function getExpertises($nome) 
  {
    $sql = "select resposta expertise, count(*) from tab_enquete_item group by resposta";
    
    try {
      $db = $this->getDBConn();
      $stmt = $db->prepare($sql);  
      $stmt->execute();
      $result = $stmt->fetchAll(PDO::FETCH_OBJ);
      $db = null;
      echo '{"records":'.json_encode($result).'}';
    } catch(PDOException $e) {
      echo '{"error":{"text":'. $e->getMessage() .'}}'; 
    }
  }
  
  

  public function getById($id) 
  {
    $sql = $this->_select . " WHERE i.id = :id ";

    try {
      $db = $this->getDBConn();
      $stmt = $db->prepare($sql);  
      $stmt->bindParam("id", $id);
      $stmt->execute();
      $result = $stmt->fetchObject();  
      $db = null;
      echo json_encode($result); 
    } catch(PDOException $e) {
      echo '{"error":{"text":'. $e->getMessage() .'}}'; 
    }
  }

  
  public function merge($vo) 
  {
      
    //echo $vo;
    $vo = json_decode($vo);

    //var_dump( $vo );
    //echo $vo->nome;
    //$vo = json_decode($vo) ; 
    //echo $vo;
    //echo $vo->id . $vo->enquete_id . $vo->nome . $vo->pergunta . $vo->resposta;
    //echo json_encode($vo);
    
     
     
    try {
      $db = $this->getDBConn();
      $sql = $this->_select_item . " WHERE id = :id ";
      $stmt = $db->prepare($sql);  
      $stmt->bindParam("id", $vo->id);
      //$stmt->bindParam("enquete_id", $vo->enquete_id);
      //$stmt->bindParam("nome", $vo->nome);
      //$stmt->bindParam("pergunta", $vo->pergunta);
      //$stmt->bindParam("resposta", $vo->resposta);
      $stmt->execute();
      $result = $stmt->fetchObject();  
         
      if ( $vo->id > 0 ) {
         $this->update($vo);
      }
      else{
          $this->insert($vo);
          //$sql = "SELECT LAST_INSERT_ID() FROM DUAL"; 
         // $stmt = $db->prepare($sql);  
         // $stmt->execute();
         // $result = $stmt->fetchAll(PDO::FETCH_OBJ); var_dump($result);  
         // $db = null;
         // $vo->id = $result[0];//echo $result;
      }
      
      //$this->getById($vo->id);
      
    } catch(PDOException $e) {
      echo '{"error":{"text":'. $e->getMessage() .'}}'; 
    }
      
    
  }
  
  
  public function insert($vo) 
  {
    
    //echo 'Antes: ';
    //var_dump( $vo );  
      
      
    try {
      $db = $this->getDBConn();
      $stmt = $db->prepare($this->_insert);  
      $stmt->bindParam("enquete_id", $vo->id);
      $stmt->bindParam("nome", $vo->nome);
      $stmt->bindParam("pergunta", $vo->pergunta);
      $stmt->bindParam("resposta", $vo->resposta);
      $stmt->execute();
      //echo json_encode($vo); 
      $stmt = $db->prepare('select last_insert_id() id from dual');  
      $stmt->execute();
      $res = $stmt->fetchObject();//$stmt->fetchAll(PDO::FETCH_OBJ);
      $db = null;
      $vo->id=$res->id;
      echo '{"records":'.json_encode($vo).'}'; 
      
    } catch(PDOException $e) {
      echo '{"error":{"text":'. $e->getMessage() .'}}'; 
    }
     
      
     
  }

  public function update($vo) 
  {
    try {
      $db = $this->getDBConn();
      $stmt = $db->prepare($this->_update);  
      $stmt->bindParam("resposta", $vo->resposta);
      $stmt->bindParam("id", $vo->id);
      $stmt->execute();
      $db = null;
      echo json_encode($vo); 
    } catch(PDOException $e) {
      echo '{"error":{"text":'. $e->getMessage() .'}}'; 
    } 
  }

  public function delete($id) 
  {
    try {
      $db = $this->getDBConn();
      $stmt = $db->prepare($this->_delete);  
      $stmt->bindParam("id", $id);
      $stmt->execute();
      $db = null;
      echo 'ok';
    } catch(PDOException $e) {
      echo '{"error":{"text":'. $e->getMessage() .'}}'; 
    }
  }

  public function deleteAll($enquete_id) 
  {
    try {
      $db = $this->getDBConn();
      $stmt = $db->prepare($this->_delete);  
      $stmt->bindParam("enquete_id", $enquete_id);
      $stmt->execute();
      $db = null;
      echo 'ok';
    } catch(PDOException $e) {
      echo '{"error":{"text":'. $e->getMessage() .'}}'; 
    }
  }
  
  
}
