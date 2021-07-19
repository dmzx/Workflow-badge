<?php
/**
*
* @package phpBB Extension - Workflow badge
* @copyright (c) 2021 dmzx - https://www.dmzx-web.net
* @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
*
*/

namespace dmzx\workflowbadge\migrations;

class install_workflowbadge extends \phpbb\db\migration\migration
{
	public function update_data()
	{
		return [
			['custom', [[$this, 'install_bbcode_for_workflowbadge']]],
		];
	}

	public function install_bbcode_for_workflowbadge()
	{
		if (!class_exists('acp_bbcodes'))
		{
			include($this->phpbb_root_path . 'includes/acp/acp_bbcodes.' . $this->php_ext);
		}
		$bbcode_tool = new \acp_bbcodes();

		$bbcode_name = 'workflows';
		$bbcode_array = [
			'bbcode_match'			=> '[workflows]{INTTEXT1}/{INTTEXT2}[/workflows]',
			'bbcode_tpl'			=> '<a href="https://github.com/{INTTEXT1}/{INTTEXT2}/actions/workflows/tests.yml" title="{INTTEXT1}/{INTTEXT2}"><img src="https://github.com/{INTTEXT1}/{INTTEXT2}/actions/workflows/tests.yml/badge.svg?branch=master" alt="" style="margin-bottom: -1px;" /></a>',
			'bbcode_helpline'		=> '[workflows]yourvendor/yourextension[/workflows]',
			'display_on_posting'	=> 0
		];

		$data = $bbcode_tool->build_regexp($bbcode_array['bbcode_match'], $bbcode_array['bbcode_tpl'], $bbcode_array['bbcode_helpline']);

		$bbcode_array += [
			'bbcode_tag'			=> $data['bbcode_tag'],
			'first_pass_match'		=> $data['first_pass_match'],
			'first_pass_replace'	=> $data['first_pass_replace'],
			'second_pass_match'		=> $data['second_pass_match'],
			'second_pass_replace' 	=> $data['second_pass_replace']
		];

		$sql = 'SELECT bbcode_id
			FROM ' . $this->table_prefix . "bbcodes
			WHERE LOWER(bbcode_tag) = '" . strtolower($bbcode_name) . "'
			OR LOWER(bbcode_tag) = '" . strtolower($bbcode_array['bbcode_tag']) . "'";
		$result = $this->db->sql_query($sql);
		$row_exists = $this->db->sql_fetchrow($result);
		$this->db->sql_freeresult($result);

		if ($row_exists)
		{

			$bbcode_id = $row_exists['bbcode_id'];

			$sql = 'UPDATE ' . $this->table_prefix . 'bbcodes
				SET ' . $this->db->sql_build_array('UPDATE', $bbcode_array) . '
				WHERE bbcode_id = ' . $bbcode_id;
			$this->db->sql_query($sql);
		}
		else
		{
			$sql = 'SELECT MAX(bbcode_id) AS max_bbcode_id
				FROM ' . $this->table_prefix . 'bbcodes';
			$result = $this->db->sql_query($sql);
			$row = $this->db->sql_fetchrow($result);
			$this->db->sql_freeresult($result);

			if ($row)
			{
				$bbcode_id = $row['max_bbcode_id'] + 1;

				if ($bbcode_id <= NUM_CORE_BBCODES)
				{
					$bbcode_id = NUM_CORE_BBCODES + 1;
				}
			}
			else
			{
				$bbcode_id = NUM_CORE_BBCODES + 1;
			}

			if ($bbcode_id <= BBCODE_LIMIT)
			{
				$bbcode_array['bbcode_id'] = (int) $bbcode_id;

				$this->db->sql_query('INSERT INTO ' . $this->table_prefix . 'bbcodes ' . $this->db->sql_build_array('INSERT', $bbcode_array));
			}
		}
	}
}
