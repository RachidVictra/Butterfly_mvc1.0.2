<?php

namespace App\Config\Models;

use App\Config\Application as App;
use \PDO;
use \PDOException;

/**
 * AppModel File : Model principal.
 *
 * @author Rachid
 */
class AppModel extends App\Application {

	public $table;
	private $from;
	private $simultaneousConnections = 0;

	/**
	 * Const
	 */
	public function __construct($model = NULL) {
		if ($model == NULL)
			$model = str_replace('Model', '', get_class($this));
		$this -> table = $this -> getTable($model);
		$this -> from = $this -> getTable($model);
	}

	/**
	 *
	 * @param type $models
	 */
	public function hasMany($models = array()) {
		foreach ($models as $value) {
			$this -> from .= " INNER JOIN " . $this -> getTable($value) . " ON $this->table.id = " . $this -> getTable($value) . ".$this->table" . "_id ";
		}
	}

	/**
	 *
	 * @param type $models
	 */
	public function hasOne($models = array()) {
		foreach ($models as $value) {
			$this -> from .= " LEFT OUTER JOIN " . $this -> getTable($value) . " ON " . $this -> table . "." . $this -> getTable($value) . "_id = " . $this -> getTable($value) . ".id";
		}
	}

	/**
	 * find a rows from model specified.
	 * @param type $args
	 * @return type
	 */
	public function find($args = array()) {
		$sql = null;
		if (!is_array($args))
			$this -> setError('Erreur ', "You should passed the params like :<br>
                                            <b>* List of All rows the table $this->table : </b> find(array('All'))<br>
                                            <b>* First row the table $this->table : </b> find(array('Fist'))<br>
                                            <b>* Specifie the Fields : </b> find(array('Fields'=>array('field1', 'field2', ..., fieldn)))<br>
                                            <b>* Specifie the conditions : </b> find(array('conditions'=>array('field1'=>'1')))<br>
                                            <b>* Specifie the conditions with clause And, Or : </b> find(array('conditions'=>array('field1'=>'1', 'And'=>array('id'=>'1'))))<br>");
		
else {
			$sql = "SELECT * FROM $this->from ";
			$condition = '';
			if (in_array('All', $args) && array_key_exists('Conditions', $args) || in_array('First', $args) && array_key_exists('Conditions', $args)) {
				$condition = $args['Conditions'];
				if (!empty($condition))
					if(is_numeric($condition))
						$condition = "WHERE id = $condition";
					else
						$condition = "WHERE $condition";
			}

			if (array_key_exists('Fields', $args)) {
				$fields = NULL;
				$last = count($args['Fields']);
				$i = 1;
				foreach ($args['Fields'] as $v) {
					if ($i == $last)
						$fields .= $v;
					else
						$fields .= "$v, ";
					$i++;
				}

				if ($fields != NULL)
					$sql = str_replace('*', $fields, $sql);
			}

			$order = '';
			if (array_key_exists('Order', $args)) {
				$first = true;
				foreach ($args['Order'] as $key => $value) {
					if ($first) {
						$order .= " Order By $key $value";
						$first = FALSE;
					} else
						$order .= ", $key $value";
				}
			}

			$group = '';
			if (array_key_exists('Group', $args)) {
				$first = true;
				foreach ($args['Group'] as $key => $value) {
					if ($first) {
						$group .= " Group By $value";
						$first = FALSE;
					} else
						$group .= ", $value";
				}
			}

			$having = '';
			if (!empty($group) && array_key_exists('Having', $args)) {
				$having = $args['Having'];
				if (!empty($having))
					$having = " Having $having";
			}

			if (in_array('All', $args) && array_key_exists('Limit', $args)) {
				$limit = $args['Limit'];
				$sql = $sql . $condition . $group . $having . $order . " LIMIT $limit[0], $limit[1]";
			} else if (in_array('All', $args)) {
				$sql = $sql . $condition . $group . $having . $order;
			}

			if (in_array('First', $args)) {
				$sql = $sql . $condition . $group . $having . $order . " LIMIT 1";
			}

			if (in_array('Last', $args)) {
				$sql = $sql . $condition . $group . $having . $order . " Order by id desc LIMIT 1";
			}

			return $this -> prepareSql($sql, TRUE);
		}
	}

	/**
	 * Execute the SQL Query.
	 * @param type $sql : SQL Query.
	 * @param type $hasResult : TRUE If there'sreturn Data | FALSE If there's not return Data.
	 * @return Result.
	 */
	public function executeSQL_Query($sql = NULL, $hasResult = TRUE) {
		return $this -> prepareSql($sql, $hasResult);
	}

	/**
	 * Save : Insert Or Update.
	 * @param type $listfields : list Of fields.
	 * @param type $id.
	 */
	public function save($listfields = array(), $id = NULL) {
		$set = NULL;
		$last = count($listfields);
		$i = 1;
		$sql = NULL;

		foreach ($listfields as $key => $value) {
			if (is_numeric($value))
				$set .= strtolower($key) . " = $value";
			else
				$set .= strtolower($key) . " = '" . str_replace("'", "&#39;", $value) . "'";

			if ($i !== $last)
				$set .= ', ';
			$i++;
		}

		if ($id === NULL) {
			$sql = "INSERT INTO $this->table SET $set";
		} else {
                    if(is_numeric($id))
			$sql = "UPDATE $this->table SET $set WHERE $this->table.id = $id";
                    else 
                        $sql = "UPDATE $this->table SET $set WHERE $id";
		}

		return $this -> prepareSql($sql, FALSE);
	}

	/**
	 *
	 * @param type $sql : SQL Query.
	 * @param type $hasResult : TRUE Language Definition Data | FALSE Language Manipulation Data.
	 * @return type
	 */
	private function prepareSql($sql, $hasResult = TRUE) {
		if ($this -> getConnection()) {
			if ($sql !== NULL) {
				$this -> beforeExecutionRequest();
				try {
					$statement = $this -> pdo -> prepare($sql);
					$this -> simultaneousConnections++;
					if (!$this -> limitTransactConDb())
						$this -> refreshConnection();
					if ($statement -> execute()) {
						$this -> afterExecutionRequest($sql, $statement -> rowCount(), $statement -> errorInfo());
						if ($hasResult)
							return $statement -> fetchAll(PDO::FETCH_OBJ);
					}
				} catch (PDOException $e) {
					$this -> setError("Error Db - ", $e -> getMessage());
				}
			}
		}
	}

	/**
	 * Check if the number is exceeded for a transaction connextion.
	 * @return TRUE | FALSE.
	 */
	private function limitTransactConDb() {
		return ($this -> simultaneousConnections <= $this -> nbOfSimultaneousConnections) ? 1 : 0;
	}

	/**
	 * Delete.
	 * @param type $id.
	 * @return type.
	 */
	public function delete($id = NULL) {
		if ($id !== NULL) {
			if(is_numeric($id))
				$sql = "DELETE FROM $this->table WHERE $this->table.id = $id";
			else
				$sql = "DELETE FROM $this->table WHERE $id";
			return $this -> prepareSql($sql, FALSE);
		}
	}

	/**
	 * Pangination.
	 * @param type $nbLine : number of line affected in the return.
	 * @param type $page : number the page.
	 * @return type Array : content the data and number of page.
	 */
	public function pagination($nbLine, $page, $fields = array(), $conditions = NULL) {
		$nombreTotalLigne = count($this -> find(array('All', 'Fields' => $fields, 'Conditions' => $conditions)));
		$pageCount = ceil($nombreTotalLigne / $nbLine);
		$premierListe = abs(($page - 1) * $nbLine);

		if (!empty($conditions) && !empty($fields))
			$data = $this -> find(array('All', 'Fields' => $fields, 'Conditions' => $conditions, 'Limit' => array($premierListe, $nbLine)));
		else if (!empty($conditions))
			$data = $this -> find(array('All', 'conditions' => $conditions, 'Limit' => array($premierListe, $nbLine)));
		else if (!empty($fields))
			$data = $this -> find(array('All', 'Fields' => $fields, 'Limit' => array($premierListe, $nbLine)));
		else
			$data = $this -> find(array('All', 'Limit' => array($premierListe, $nbLine)));
		return array('data' => $data, 'pages' => $pageCount);
	}

	private function beforeExecutionRequest() {
		$t1 = explode(" ", microtime());
		$t2 = explode(".", $t1[0]);
		return $t1[1] . "." . $t2[1];
	}

	private function afterExecutionRequest($sql, $nbRows, $error = NULL) {
		$er = $error[2];
		$t3 = explode(" ", microtime());
		$t4 = explode(".", $t3[0]);
		$t4 = $t3[1] . "." . $t4[1];
		$t5 = ($t4 - $this -> beforeExecutionRequest()) * 1000;
		$ms = number_format(abs($t5 * 100), 2, ',', '');
		if ($this -> getMode() === 0) {
			self::$sql_dump[] = array('request' => $sql, 'error' => $er, 'time' => $ms, 'resultat' => $nbRows);
		}
	}

	/**
	 * Stored Procedure.
	 * @param type $procedure : Stored Procedure Name.
	 * @param type $params : Input | output parameters.
	 * @return type : Data Output.
	 */
	public function storedProcedure($procedure, $params = array()) {
		if ($this -> getConnection()) {
			$this -> beforeExecutionRequest();
			try {
				$request = "CALL $procedure(";
				$i = 0;

				foreach (array_keys($params) as $key) {
					if (strtolower($key) == 'in')
						foreach ($params[$key] as $in)
							($i++ == 0) ? $request .= "'$in'" : $request .= ", '$in'";
					
					$outing = FALSE;
					$select = '';
					$j = 0;
					if (strtolower($key) == 'out')
						foreach ($params[$key] as $out) {
							$outing = TRUE;
							($i++ == 0) ? $request .= "@$out" : $request .= ", @$out";
							($j++ == 0) ? $select .= "SELECT @$out as $out" : $select .= ", @$out as $out";
						}

				}
				$request .= ")";

				$this -> prepareSql($request, FALSE);
				if ($outing)
					return $this -> prepareSql($select, TRUE);
			} catch (PDOException $e) {
				$this -> setError("Error Db - ", $e -> getMessage());
			}
		}
	}

}
