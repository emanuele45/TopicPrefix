<?php
/**
 * Topics Prefix
 *
 * @author  emanuele
 * @license BSD http://opensource.org/licenses/BSD-3-Clause
 *
 * @version 0.0.5
 */

/**
 * This class primarily deals with interacting with the topic_prefix table
 * associating topics to prefixes (add, update, delete, count, etc) crud
 *
 * Class TopicPrefix_TcCRUD
 */
class TopicPrefix_TcCRUD
{
	/** @var \Database */
	protected $db;

	/**
	 * TopicPrefix_TcCRUD constructor.
	 */
	public function __construct()
	{
		$this->db = database();
	}

	/**
	 * Delete a prefix from a given topic id
	 *
	 * @param int $topic
	 * @return bool
	 */
	public function deleteByTopic($topic)
	{
		return $this->delete('topic', (int) $topic);
	}

	/**
	 * Delete "setter", removes a prefix by topic id and/or prefix id
	 *
	 * @param string $type topic, prefix or topic_prefix
	 * @param mixed $value
	 * @return bool
	 */
	protected function delete($type, $value)
	{
		return $this->runQuery('
			DELETE
			FROM {db_prefix}topic_prefix',
			$type, $value
		);
	}

	/**
	 * Helper function to build a query statement to run against our prefix tables
	 *
	 * @param string $statement sql
	 * @param string $type one of topic, prefix, topic_prefix
	 * @param mixed $value array of ints for topics / prefixes
	 * @return mixed false on failure
	 */
	protected function runQuery($statement, $type, $value)
	{
		$known_types = array(
			'topic_board' => 'p.id_topic IN ({array_int:id_topic})',
			'topic' => 'id_topic IN ({array_int:id_topic})',
			'prefix' => 'id_prefix IN ({array_int:id_prefix})',
			'topic_prefix' => 'id_topic IN ({array_int:id_topic}) AND id_prefix IN ({array_int:id_prefix})',
		);

		if (!isset($known_types[$type]))
		{
			return false;
		}

		return $this->db->query('', $statement . '
			WHERE ' . $known_types[$type] . (isset($value['board']) ? '
				AND FIND_IN_SET({int:board}, id_boards)' : ''),
			array(
				'id_topic' => isset($value['id_topic']) ? (array) $value['id_topic'] : (array) $value,
				'id_prefix' => isset($value['id_prefix']) ? (array) $value['id_prefix'] : (array) $value,
			)
		);
	}

	/**
	 * Delete a prefix by a given prefix id
	 *
	 * @param int $prefix
	 * @return bool
	 */
	public function deleteByPrefix($prefix)
	{
		return $this->delete('prefix', (int) $prefix);
	}

	/**
	 * Fetch a prefix by id, use id to fetch prefix_id/topic_id list and
	 * anything else to get the prefix_id, prefix name / topic list
	 *
	 * @param int $prefix
	 * @param string $what
	 * @return mixed
	 */
	public function getByPrefix($prefix, $what = 'id')
	{
		$method = $this->method($what);
		$result = $this->{$method}('topic_prefix', (int) $prefix);

		return $this->result($result);
	}

	/**
	 * Helper function to call the right fetch method
	 *
	 * @param string $what
	 * @return string
	 */
	protected function method($what)
	{
		if ($what === 'id')
		{
			return 'read';
		}

		if ($what === 'full')
		{
			return 'load';
		}

		return 'load';
	}

	protected function result($result, $index = null)
	{
		// This is likely an error (false)
		if (!is_array($result))
		{
			return $result;
		}

		// An un-indexed result
		if ($index === null)
		{
			return $this->singleRes($result);
		}

		$other_idx = null;

		// Indexed wanted, but index doesn't exist
		if (!isset($result[0][$index]))
		{
			return $this->singleRes($result);
		}
		elseif (is_array($result[0][$index]) && count($result[0][$index]) === 2)
		{
			foreach ($result[0] as $key => $val)
			{
				if ($key !== $index)
				{
					$other_idx = $key;
				}
			}
		}

		$return = array();
		foreach ($result as $res)
		{
			$return[$res[$index]] = $other_idx === null ? $res : $res[$other_idx];
		}

		return $return;
	}

	protected function singleRes($result)
	{
		if (is_array($result) && count($result) !== 1)
		{
			return $result;
		}

		return $result[0];
	}

	public function getByTopicPrefix($topic, $prefix, $what = 'id')
	{
		$method = $this->method($what);
		$result = $this->{$method}('topic_prefix', array('id_topic' => (int) $topic, 'id_prefix' => (int) $prefix));

		return $this->result($result);
	}

	public function updateByPrefixTopic($id_topic, $id_prefix = null)
	{
		$current = $this->getByTopic($id_topic);

		// If the prefix is empty, just cleanup any potential mess and live happy!
		if (empty($id_prefix))
		{
			return $this->deleteByTopicPrefix($id_topic, $current);
		}

		// If the record doesn't exist it's time to create it
		if (empty($current))
		{
			return $this->create($id_topic, $id_prefix);
		}

		// If we already have one, then we have to change it
		// (provided the new one is different)
		return $this->update((int) $id_prefix, 'topic_prefix', array(
			'id_topic' => (int) $id_topic,
			'id_prefix' => (int) $id_prefix)
		);
	}

	/**
	 * Fetch the prefixes associated with a topic
	 *
	 * @param int[] $topics
	 * @param string $what
	 * @return array|mixed
	 */
	public function getByTopic($topics, $what = 'id')
	{
		$method = $this->method($what);
		switch ($what)
		{
			case 'id':
				$result = $this->{$method}('topic', $topics);
				break;
			case 'full':
				$result = $this->{$method}('topic_board', $topics);
				break;
			default:
				$result = $this->{$method}('topic', $topics);
		}

		return $this->result($result, 'id_topic');
	}

	public function deleteByTopicPrefix($topic, $prefix)
	{
		return $this->delete('topic_prefix', array('id_topic' => (int) $topic, 'id_prefix' => (int) $prefix));
	}

	public function create($topic, $prefix_id)
	{
		$this->db->insert('',
			'{db_prefix}topic_prefix',
			array(
				'id_prefix' => 'int',
				'id_topic' => 'int',
			),
			array(
				$prefix_id,
				$topic
			),
			array('id_prefix', 'id_topic')
		);
	}

	/**
	 * @param int $new_prefix
	 * @param string $type topic_prefix
	 * @param int $value
	 * @return mixed
	 */
	protected function update($new_prefix, $type, $value)
	{
		$value['new_prefix'] = $new_prefix;

		return $this->runQuery('
			UPDATE {db_prefix}topic_prefix
			SET id_prefix = {int:new_prefix}',
			$type, $value
		);
	}

	/**
	 * Return the number of times a prefix has been used
	 *
	 * @param $id_prefix
	 * @return mixed
	 */
	public function countByPrefix($id_prefix)
	{
		return $this->count('prefix', (int) $id_prefix);
	}

	/**
	 * Helper function to do counting of items in the topic prefix table
	 *
	 * @param string $type prefix
	 * @param int[] $value
	 * @return int
	 */
	protected function count($type, $value)
	{
		$request = $this->runQuery('
			SELECT 
				COUNT(*)
			FROM {db_prefix}topic_prefix',
			$type, $value
		);
		list ($num) = $this->db->fetch_row($request);
		$this->db->free_result($request);

		return $num;
	}

	/**
	 * Helper function to load the list of topics associated with a given prefix id
	 *
	 * @param string $type
	 * @param mixed $value
	 * @return array
	 */
	protected function read($type, $value)
	{
		$request = $this->runQuery('
			SELECT 
				id_topic, id_prefix
			FROM {db_prefix}topic_prefix',
			$type, $value
		);
		$return = array();
		while ($row = $this->db->fetch_assoc($request))
		{
			$return[] = $row;
		}
		$this->db->free_result($request);

		return $return;
	}

	/**
	 * Helper function to load the list of topics associated with a given prefix id
	 *
	 * @param string $type prefix, topic, topic_prefix, topic_board
	 * @param mixed $value
	 * @return array
	 */
	protected function load($type, $value)
	{
		$request = $this->runQuery('
			SELECT 
				p.id_topic, p.id_prefix, pt.prefix' . ($type === 'topic_board' ? ', tp.id_board' : '') . '
			FROM {db_prefix}topic_prefix AS p
				LEFT JOIN {db_prefix}topic_prefix_text AS pt ON (p.id_prefix = pt.id_prefix)' . ($type === 'topic_board'
				? 'LEFT JOIN {db_prefix}topics AS tp ON (p.id_topic = tp.id_topic)' : ''),
			$type, $value
		);
		$return = array();
		while ($row = $this->db->fetch_assoc($request))
		{
			$return[] = $row;
		}
		$this->db->free_result($request);

		return $return;
	}
}
