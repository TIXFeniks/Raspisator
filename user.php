<?php

$SUCCESS = ['success'=>true];

function stmt_to_dict($stmt, $index = "id"){
	$res = [];
	while($row = $stmt->fetch(PDO::FETCH_ASSOC))
    {
	    $res[$row[$index]] = $row;
    }
	return $res;
}


class USER
{
    private $db;
 
    function __construct($DB_con)
    {
      $this->db = $DB_con;
    }
 
    public function register($name,$uname,$umail,$upass)
    {
       try
       {
           $new_password = password_hash($upass, PASSWORD_DEFAULT);
           $stmt = $this->db->prepare("INSERT INTO users(user_name,user_email,user_pass, name) 
                                                       VALUES(:uname, :umail, :upass, :name)");
           $stmt->bindparam(":uname", $uname);
           $stmt->bindparam(":name", $name); 
           $stmt->bindparam(":umail", $umail);
           $stmt->bindparam(":upass", $new_password);            
           $stmt->execute(); 
   
           //return $stmt; 
           http_response_code(201);//FIX ME NOT SENDING
           return ['success'=>'OK'];
       }
       catch(PDOException $e)
       {
          http_response_code(400);//FIX ME NOT SHOWING
          return ['error' =>$e->getMessage()];
       }    
    }
 
    public function login($uname,$upass)
    {
       try
       {
          $stmt = $this->db->prepare("SELECT * FROM users WHERE user_name=:uname OR user_email=:umail LIMIT 1");
          $stmt->execute(array(':uname'=>$uname, ':umail'=>$uname));
          $userRow=$stmt->fetch(PDO::FETCH_ASSOC);
          if($stmt->rowCount() > 0)
          {
             if(password_verify($upass, $userRow['user_pass']))
             {
                $_SESSION['user_session'] = $userRow['user_id'];
                $_SESSION['user_name'] = $userRow['user_name'];
                return ['isLoggedin'=>true, 'name' => $_SESSION['user_name']];
             }
          }
          http_response_code(400);
          return ['isLoggedin'=>false];
       }
       catch(PDOException $e)
       {
          http_response_code(400);
          return ['isLoggedin'=>false];

           //echo $e->getMessage();
       }
   }

   public function save_project_data($id, $data) {
      try
       {
          $result = [];
          $stmt = $this->db->prepare("UPDATE projects SET project_data = :pd WHERE id = :pid");
          $stmt->bindparam(":pid", $id, PDO::PARAM_INT);
          $stmt->bindparam(":pd", $data);
          $stmt->execute();
          //$res = $stmt->fetch(PDO::FETCH_ASSOC);
          http_response_code(200);  
          return ;
       }
       catch(PDOException $e)
       {
        //  http_response_code(400);
          echo $e->getMessage();
      //return [];

           
       }
   }

   public function get_all_users()
   {
      try
       {
          $stmt = $this->db->prepare("SELECT user_id, user_name, user_email, name FROM users");
          $stmt->execute();
          $userRow=$stmt->fetchall(PDO::FETCH_ASSOC);
          return $userRow;
       }
       catch(PDOException $e)
       {
           echo $e->getMessage();
       }
   }
   public function get_school_data($sid)
   {
   	   try
       {
		   
		  $stmt = $this->db->prepare("SELECT id, name FROM subjects WHERE school_id = :id");
          $stmt->bindparam(":id", $sid, PDO::PARAM_INT);
		  $stmt->execute();
		  $subjects = stmt_to_dict($stmt);
		  $stmt = $this->db->prepare("SELECT id, grade_name, grade_number FROM grades WHERE school_id = :id");
          $stmt->bindparam(":id", $sid, PDO::PARAM_INT);
		  $stmt->execute();
		  $grades= stmt_to_dict($stmt);
		  
		  $stmt = $this->db->prepare("SELECT users.user_id, name FROM users JOIN role_user_school_relation ON role_user_school_relation.user_id = users.user_id WHERE role_user_school_relation.school_id = :id");
          $stmt->bindparam(":id", $sid, PDO::PARAM_INT);
		  $stmt->execute();
		  $teachers= stmt_to_dict($stmt, 'user_id');
		  
		  $all = ['subjects' => $subjects, 'grades' => $grades, 'teachers' => $teachers];
          return $all;
       }
       catch(PDOException $e)
       {
           echo $e->getMessage();
       }
   }
   public function get_profile_data()
   {
   	   try
       {
          $stmt = $this->db->prepare("SELECT user_name, user_email, name FROM users WHERE user_id = :id");
          $stmt->bindparam(":id", $_SESSION['user_session'], PDO::PARAM_INT);
		  $stmt->execute();
		  $one=$stmt->fetchall(PDO::FETCH_ASSOC);
		   
		  $stmt = $this->db->prepare("SELECT school_id, role_id, is_approved FROM role_user_school_relation WHERE user_id = :id");
          $stmt->bindparam(":id", $sid, PDO::PARAM_INT);
		  $stmt->execute();
		  $two=$stmt->fetchall(PDO::FETCH_ASSOC);
		  
		  $all = ['one' => $one, 'two' => $two];
          return $all;
       }
       catch(PDOException $e)
       {
           echo $e->getMessage();
       }
   }
   public function get_project($current_project_id, $return_school_info)
   {
      try
       {
      	$result = [];
          $stmt = $this->db->prepare("SELECT project_name, project_data, school_id, owner_id, start, finish, creation_time, lessons_per_day FROM projects WHERE id = :id");
          $stmt->bindparam(":id", $current_project_id, PDO::PARAM_INT);
          $stmt->execute();
          $row = $stmt->fetch(PDO::FETCH_ASSOC);
		  if($return_school_info)
          	  return ['project'=>$row, 'school'=>$this->get_school_data($row['school_id'])];
		  else
			  return $row;
       }
       catch(PDOException $e)
       {
          http_response_code(400);
          echo $e->getMessage();
          return [];

           
       }
     }
   public function get_all_schools()
   {
      try
       {
		  $result = [];
          $stmt = $this->db->prepare("SELECT name FROM schools");
		  $stmt->execute();
		  while ($row = $stmt->fetch(PDO::FETCH_ASSOC)){
			 $result[] = $row;
		  }
          return $result;
       }
	   catch(PDOException $e)
       {
          http_response_code(400);
          echo $e->getMessage();
		  return [];

           
       }
   }
   public function get_my_schools()
   {
      try
       {
		  $result = [];
          $stmt = $this->db->prepare("SELECT DISTINCT schools.name, schools.id FROM schools JOIN role_user_school_relation 
		  ON role_user_school_relation.school_id = schools.id and role_user_school_relation.user_id = :oid");
		  $stmt->bindparam(":oid", $_SESSION['user_session'], PDO::PARAM_INT);
		  $stmt->execute();
		  $result = $stmt->fetchall(PDO::FETCH_ASSOC);
          return $result;
       }
	   catch(PDOException $e)
       {
          http_response_code(400);
          echo $e->getMessage();
		  return [];

           
       }
   }
   public function get_lessons($current_project_id)
   {
      try
       {
		  $result = [];
          $stmt = $this->db->prepare("
		  	SELECT
            lessons.id,
			lessons.teacher_id,
			lessons.subject_id,
			lessons.grade_id
			from
			lessons
			join projects
			on projects.school_id = lessons.school_id 
			where projects.id = :current_project_id");
          $stmt->bindparam(":current_project_id", $current_project_id, PDO::PARAM_INT);
		  $stmt->execute();
		  $result = $stmt->fetchall(PDO::FETCH_ASSOC);
          return $result;
       }
       catch(PDOException $e)
       {
          http_response_code(400);
          echo $e->getMessage();
		  return [];

           
       }
   }


   public function get_school_lessons($school_id)
   {
      try
       {
      $result = [];
          $stmt = $this->db->prepare("
        SELECT
            lessons.id,
      lessons.teacher_id,
      lessons.subject_id,
      lessons.grade_id
      from
      lessons 
      where lessons.school_id = :school_id");
          $stmt->bindparam(":school_id", $school_id, PDO::PARAM_INT);
      $stmt->execute();
      $result = $stmt->fetchall(PDO::FETCH_ASSOC);
          return $result;
       }
       catch(PDOException $e)
       {
          http_response_code(400);
          echo $e->getMessage();
      return [];

           
       }
   }

   public function is_loggedin()
   {
      if(isset($_SESSION['user_session']))
      {
         return  ['isLoggedin'=>true, 'name'=>$_SESSION['user_name']];
      } 
      else
      {
        return  ['isLoggedin'=>false];
      }
   }
 
   public function redirect($url)
   {
       header("Location: $url");
   }
   public function set_role_user_school_relation($school_id, $user_id, $role_id, $is_approved)
   {
	   try
	   {
           $stmt = $this->db->prepare("INSERT INTO role_user_school_relation (user_id, role_id, school_id, is_approved) VALUES (:user_id, :role_id, :school_id, :is_approved)");
	       $stmt->bindparam(":user_id", $user_id, PDO::PARAM_INT);
		   $stmt->bindparam(":role_id", $role_id, PDO::PARAM_INT);
		   $stmt->bindparam(":school_id", $school_id, PDO::PARAM_INT);
	       $stmt->bindparam(":is_approved", $is_approved, PDO::PARAM_INT);
	   	   $stmt->execute();
		   http_response_code(200);
           return ['success'=>'OK'];
	   }
	   catch(PDOException $e)
	   {
		   http_response_code(400);//FIX ME NOT SENDING
           return ['error'=>$e->getMessage()];
       }
   }
   public function add_lesson($subject_id, $teacher_id, $grade_id)
   {
       try
	   {
           $stmt = $this->db->prepare("INSERT INTO lessons (subject_id, teacher_id, grade_id) VALUES (:subject_id, :teacher_id, :grade_id)");
	       $stmt->bindparam(":subject_id", $subject_id);
	       $stmt->bindparam(":teacher_id", $teacher_id, PDO::PARAM_INT);
		   $stmt->bindparam(":grade_id", $grade_id, PDO::PARAM_INT);
	   	   $stmt->execute();
		   http_response_code(200);
           return ['success'=>'OK'];
	   }
	   catch(PDOException $e)
	   {
		   http_response_code(400);//FIX ME NOT SENDING
           return ['error'=>$e->getMessage()];
       }
   }
   public function delete_lesson($lesson_id)
   {
       try
	   {
           $stmt = $this->db->prepare("DELETE FROM lessons WHERE id = :lid");
		   $stmt->bindparam(":lid", $lesson_id, PDO::PARAM_INT);
	   	   $stmt->execute();
		   http_response_code(200);
           return ['success'=>'OK'];
	   }
	   catch(PDOException $e)
	   {
		   http_response_code(400);//FIX ME NOT SENDING
           return ['error'=>$e->getMessage()];
       }
   }
   public function delete_project($project_id)
   {
       try
	   {
           $stmt = $this->db->prepare("DELETE FROM projects WHERE id = :pid");
		   $stmt->bindparam(":pid", $project_id, PDO::PARAM_INT);
	   	   $stmt->execute();
           
   		   http_response_code(200);
           return ['success'=>'OK'];
	   }
	   catch(PDOException $e)
	   {
		   http_response_code(400);//FIX ME NOT SENDING
           return ['error'=>$e->getMessage()];
       }
   }
   public function add_project($p_name, $s_id, $start, $finish, $lessons_per_day)
   {
       try
	   {
           if(isset($_SESSION['user_session']))
           {
               $stmt = $this->db->prepare("INSERT INTO projects (owner_id, project_name, school_id, start, finish, lessons_per_day) VALUES (:oid, :pr_name, :s_id, :start, :finish, :lpd)");
	           $stmt->bindparam(":oid", $_SESSION['user_session'], PDO::PARAM_INT);
			   $stmt->bindparam(":s_id", $s_id, PDO::PARAM_INT);
			   $stmt->bindparam(":lpd", $lessons_per_day, PDO::PARAM_INT);
               $stmt->bindparam(":pr_name", $p_name);
			   $stmt->bindparam(":start", $start);
			   $stmt->bindparam(":finish", $finish);
	   	       $stmt->execute();
		       http_response_code(200);
               return ['success'=>'OK', 'project_id' => $this->db->lastInsertId()];
           }
       }
	   catch(PDOException $e)
	   {
		   //http_response_code(400);//FIX ME NOT SENDING
           return ['error'=>$e->getMessage()];
       }
   }
   public function add_school($school_name)
   {
       try
	   {
           if(isset($_SESSION['user_session']))
           {
               $stmt = $this->db->prepare("INSERT INTO schools (name) VALUES (:school_name)");
               $stmt->bindparam(":school_name", $school_name);
	   	       $stmt->execute();
		       http_response_code(200);
               return ['success'=>'OK'];
           }
       }
	   catch(PDOException $e)
	   {
		   http_response_code(400);//FIX ME NOT SENDING
           return ['error'=>$e->getMessage()];
       }
   }
   public function approve($rel_id)
   {
       try
	   {
           if(isset($_SESSION['user_session']))
           {
               $stmt = $this->db->prepare("UPDATE role_user_school_relation SET is_approved=1 WHERE id = :rid");
               $stmt->bindparam(":rid", $rel_id, PDO::PARAM_INT);
	   	       $stmt->execute();
		       http_response_code(200);
               return ['success'=>'OK'];
           }
       }
	   catch(PDOException $e)
	   {
		   http_response_code(400);//FIX ME NOT SENDING
           return ['error'=>$e->getMessage()];
       }
   }
   public function add_school_timetable($timetable, $school_id)
   {
       try
	   {
           if(isset($_SESSION['user_session']))
           {
			   //var_dump ($timetable);
			   //var_dump ($timetable);
			   $t = json_decode($timetable);
			   // var_dump ($t[1]);
			   $s = "INSERT INTO school_time (school_id, lesson, start_time) VALUES ";
			   for($i = 0; $i < count($t); $i=$i+1)
			   {//todo: fix this to be secure
			       $s=$s."(".$school_id.",".$i.",".'\''.$t[$i].'\''.")";
				   if($i != count($t)-1)
					   $s=$s.',';
			   }
			   echo $s;
               $stmt = $this->db->prepare($s);
               $stmt->bindparam(":rid", $rel_id, PDO::PARAM_INT);
	   	       $stmt->execute();
		       http_response_code(200);
               return ['success'=>'OK'];
           }
       }
	   catch(PDOException $e)
	   {
		   http_response_code(400);//FIX ME NOT SENDING
           return ['error'=>$e->getMessage()];
       }
   }
   public function get_all_projects()
   {
       try
       {
		  $result = [];
          $stmt = $this->db->prepare("SELECT id, project_name FROM projects WHERE owner_id = :cid");
		  $stmt->bindparam(":cid", $_SESSION['user_session'], PDO::PARAM_INT);
          $stmt->execute();
          while ($row = $stmt->fetch(PDO::FETCH_ASSOC)){
			 $result[] = $row;
		  }
          return $result;
	   }
       catch(PDOException $e)
       {
           echo $e->getMessage();
       }
   }
   public function logout()
   {
        session_destroy();
        unset($_SESSION['user_session']);
        unset($_SESSION['user_name']);
        return $SUCCESS;
   }
}
?>
