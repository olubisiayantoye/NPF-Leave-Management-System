<?php
/**
 * Whitelist of lookup tables manageable from Settings > Lookup Management.
 * 'parent' names a table this one's rows reference (rendered as a dropdown);
 * 'extra' names an additional plain-value column some lookup tables carry
 * (e.g. ranks.hierarchy_level).
 */
function manageable_lookup_tables(): array
{
    return [
        'ranks'               => ['label' => 'Rank', 'extra' => 'hierarchy_level'],
        'banks'               => ['label' => 'Bank'],
        'sections'            => ['label' => 'Section'],
        'departments'         => ['label' => 'Department'],
        'geo_political_zones' => ['label' => 'Geopolitical Zone'],
        'tribes'              => ['label' => 'Tribe'],
        'commands'            => ['label' => 'Command'],
        'pfas'                => ['label' => 'Pension Fund Administrator (PFA)'],
        'states'              => ['label' => 'State', 'parent' => 'geo_political_zones', 'parent_col' => 'zone_id'],
        'local_governments'   => ['label' => 'Local Government Area', 'parent' => 'states', 'parent_col' => 'state_id'],
    ];
}

/** In-use checks per table, so a value still referenced by real records can't be deleted. */
function lookup_usage_checks(): array
{
    return [
        'ranks' => ["SELECT COUNT(*) FROM personnel WHERE rank_id = ?", "SELECT COUNT(*) FROM monthly_allowance_personnel WHERE rank_id = ?"],
        'banks' => ["SELECT COUNT(*) FROM personnel WHERE bank_id = ?", "SELECT COUNT(*) FROM monthly_allowance_personnel WHERE bank_id = ?"],
        'departments' => ["SELECT COUNT(*) FROM personnel WHERE department_id = ?"],
        'geo_political_zones' => ["SELECT COUNT(*) FROM personnel WHERE zone_id = ?", "SELECT COUNT(*) FROM states WHERE zone_id = ?"],
        'tribes' => ["SELECT COUNT(*) FROM personnel WHERE tribe_id = ?"],
        'commands' => ["SELECT COUNT(*) FROM personnel WHERE previous_command_id = ?"],
        'pfas' => ["SELECT COUNT(*) FROM personnel WHERE pfa_id = ?"],
        'states' => ["SELECT COUNT(*) FROM personnel WHERE state_id = ?", "SELECT COUNT(*) FROM local_governments WHERE state_id = ?"],
        'local_governments' => ["SELECT COUNT(*) FROM personnel WHERE lga_id = ?"],
        'sections' => [],
    ];
}
