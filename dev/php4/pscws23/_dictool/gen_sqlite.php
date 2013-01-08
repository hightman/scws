<?php
// convert the dict.cdb -> dict.sqlite
@unlink("dict.sqlite");
$xx = sqlite_open("dict.sqlite");
sqlite_query("CREATE TABLE _wordlist (id INTEGER NOT NULL PRIMARY KEY, word CHAR(32), freq BIGINT)", $xx);
sqlite_query("CREATE UNIQUE INDEX _wordidx ON _wordlist (word)", $xx);
sqlite_query("BEGIN TRANSACTION", $xx);

$total = 0;
$db = dba_open("dict.cdb", "r", "cdb");
if ($key = dba_firstkey($db))
{
	do {
		$value = (int) dba_fetch($key, $db);
		$sql = "INSERT INTO _wordlist (word, freq) VALUES ('$key', $value)";
		sqlite_query($sql, $xx);
		$total++;

		if ($total % 5000 == 0)
		{
			echo "$total ... \n";
			flush();
		}
	}
	while ($key = dba_nextkey($db));
}
dba_close($db);
sqlite_query("COMMIT TRANSACTION", $xx);
sqlite_close($xx);
?>
