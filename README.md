# WorldCat Linked Data for PHP

A PHP library for working with OCLC WorldCat's Linked Data implementation.

Allows you to retrieve metadata for known items (via OCLC number or ISBN) and 
retrieve the work and all other examples of work (i.e. FRBR Work/Manifestations).

Provides most of the functionality of OCLC's xID services ~~and includes a 
semi-functional class for returning data in the rough shape as xID~~. (removed this for now)

Examples:
```
$manifestation = new \WorldCatLD\Manifestation();
$manifestation->findByIsbn('9780375985294');
echo $manifestation->name;
=> "The phantom tollbooth"

get_class($manifestation->author);
=> "WorldCatLD\Entity"

echo $manifestation->creator->name;
=> "Norton Juster"

$work = $manifestation->getWork();
$manifestations = $work->getWorkExample();
count($manifestations);
=> 152

foreach ($manifestations as $id => $exampleOfWork) {
    echo $exampleOfWork->name . "\n";
}

=>  The phantom tollbooth
    The phantom tollbooth
    The phantom tollbooth
    The phantom tollbooth
    The phantom tollbooth
    The phantom tollbooth
    La caseta magica.
    Milos ganz und gar unmögliche Reise
    The phantom tollbooth
    The phantom tollbooth
    收费亭里的小米洛 = The phantom tollbooth
    The phantom tollbooth
    המגדל הפּורח באויר.
    The phantom tollbooth
    The phantom tollbooth
    The phantom tollbooth
    The phantom tollbooth
    The phantom tollbooth
    Il casello magico
    ...
```
