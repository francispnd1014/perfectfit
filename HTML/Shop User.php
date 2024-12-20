<?php
session_start();
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");
if (!isset($_SESSION['email'])) {
    header("Location: Login.php");
    exit();
}

$servername = "localhost";
$username = "root";
$password = "g8gbV0noL$3&fA6x-GAMER";
$dbname = "perfectfit";


$conn = new mysqli($servername, $username, $password, $dbname);


if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}


$email = $_SESSION['email'];

$query = "SELECT fname, sname, pfp FROM users WHERE email='$email'";
$result = $conn->query($query);

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $fullname = $row['fname'] . ' ' . $row['sname'];
    $profile_picture = $row['pfp'];
    $fname = $row['fname'];
    $_SESSION['fullname'] = $fullname;
    $_SESSION['profile_picture'] = $profile_picture;
} else {
    header("Location: Login.php");
    exit();
}


$theme_query = "SELECT DISTINCT theme FROM product";
$theme_result = $conn->query($theme_query);

$themes = [];
if ($theme_result->num_rows > 0) {
    while ($row = $theme_result->fetch_assoc()) {
        $themes[] = $row['theme'];
    }
}


$color_query = "SELECT DISTINCT color FROM product";
$color_result = $conn->query($color_query);

$colors = [];
if ($color_result->num_rows > 0) {
    while ($row = $color_result->fetch_assoc()) {
        $colors[] = $row['color'];
    }
}


$size_query = "SELECT GROUP_CONCAT(DISTINCT size SEPARATOR ',') AS sizes FROM product";
$size_result = $conn->query($size_query);

$sizes = [];
if ($size_result->num_rows > 0) {
    $row = $size_result->fetch_assoc();
    $sizes = array_unique(explode(',', $row['sizes']));
}


$selectedThemes = [];
$selectedColors = [];
$selectedSizes = [];
if ($_SERVER['REQUEST_METHOD'] === 'GET' && !empty($_GET['selectedTags'])) {
    $selectedTags = json_decode($_GET['selectedTags'], true);
    foreach ($selectedTags as $tagData) {
        if ($tagData['type'] === 'theme') {
            $selectedThemes[] = $tagData;
        } elseif ($tagData['type'] === 'color') {
            $selectedColors[] = $tagData;
        } elseif ($tagData['type'] === 'size') {
            $selectedSizes[] = $tagData;
        }
    }
}

$select_query = "SELECT * FROM product WHERE 1=1";


if (!empty($selectedThemes)) {
    $themeConditions = [];
    foreach ($selectedThemes as $themeData) {
        $theme = mysqli_real_escape_string($conn, $themeData['tag']);
        $state = $themeData['state'];

        if ($state === 'check') {
            $themeConditions[] = "FIND_IN_SET('$theme', theme)";
        } elseif ($state === 'x') {
            $themeConditions[] = "NOT FIND_IN_SET('$theme', theme)";
        }
    }
    if (!empty($themeConditions)) {
        $select_query .= " AND (" . implode(' OR ', $themeConditions) . ")";
    }
}


if (!empty($selectedColors)) {
    $colorConditions = [];
    foreach ($selectedColors as $colorData) {
        $color = mysqli_real_escape_string($conn, $colorData['tag']);
        $state = $colorData['state'];

        if ($state === 'check') {
            $colorConditions[] = "FIND_IN_SET('$color', color)";
        } elseif ($state === 'x') {
            $colorConditions[] = "NOT FIND_IN_SET('$color', color)";
        }
    }
    if (!empty($colorConditions)) {
        $select_query .= " AND (" . implode(' OR ', $colorConditions) . ")";
    }
}


if (!empty($selectedSizes)) {
    $sizeConditions = [];
    foreach ($selectedSizes as $sizeData) {
        $size = mysqli_real_escape_string($conn, $sizeData['tag']);
        $state = $sizeData['state'];

        if ($state === 'check') {
            $sizeConditions[] = "FIND_IN_SET('$size', size)";
        } elseif ($state === 'x') {
            $sizeConditions[] = "NOT FIND_IN_SET('$size', size)";
        }
    }
    if (!empty($sizeConditions)) {
        $select_query .= " AND (" . implode(' OR ', $sizeConditions) . ")";
    }
}

function log_user_interaction($conn, $email, $gown_id, $interaction_type)
{
    $stmt = $conn->prepare("INSERT INTO user_interactions (email, gown_id, interaction_type) VALUES (?, ?, ?)");
    $stmt->bind_param("sis", $email, $gown_id, $interaction_type);
    $stmt->execute();
    $stmt->close();
}
$gown_id = isset($_GET['id']) ? intval($_GET['id']) : 0;


if ($gown_id) {
    log_user_interaction($conn, $email, $gown_id, 'view');
}


if (isset($_GET['search']) && !empty($_GET['search'])) {
    $search = mysqli_real_escape_string($conn, $_GET['search']);
    $select_query .= " AND (name LIKE '%$search%' OR size LIKE '%$search%' OR color LIKE '%$search%' OR theme LIKE '%$search%' OR analysis LIKE '%$search%' OR tone LIKE '%$search%')";
    log_user_interaction($conn, $email, 0, 'search');
}

if (isset($_GET['sort'])) {
    $sortOption = $_GET['sort'];
    switch ($sortOption) {
        case 'top-sales':
            $select_query .= " ORDER BY CAST(tally AS UNSIGNED) DESC";
            break;
        case 'rent-price-asc':
            $select_query .= " ORDER BY CAST(price AS UNSIGNED) ASC";
            break;
        case 'rent-price-desc':
            $select_query .= " ORDER BY CAST(price AS UNSIGNED) DESC";
            break;
        default:
            $select_query .= " ORDER BY status ASC";
            break;
    }
} else {
    $select_query .= " ORDER BY status ASC";
}

$select = mysqli_query($conn, $select_query);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/x-icon" href="../IMAGES/FAV.png">
    <link rel="stylesheet" href="font/css/all.min.css">
    <link rel="stylesheet" href="../CSS/User Shop.css">
    <title>Shop</title>
    <style>

    </style>
</head>

<body>
    <div class="banner">
        <div class="navbar">
            <a href="../HTML/Home User.php"><img src="../IMAGES/RICH SABINIANS.png" class="logo">
                <ul>
                    <li><a href="../HTML/Home User.php">Home</a></li>
                    <li><a href="../HTML/Shop User.php">Shop</a></li>
                    <li class="dropdown">
                        <a href="#" class="dropbtn" onclick="toggleDropdown()"> <?php echo htmlspecialchars($fname); ?></a>
                        <div id="myDropdown" class="dropdown-content">
                            <div class="sub-menu">
                                <div class="user-info">
                                    <img src="<?php echo htmlspecialchars($profile_picture); ?>" alt="Profile Picture" width="50" height="50">
                                    <h3><?php echo htmlspecialchars($fullname); ?></h3>
                                </div>
                                <a href="../HTML/Account.php" class="sub-menu-link">
                                    <p>Profile</p>
                                </a>
                                <a href="Logout.php" class="sub-menu-link">
                                    <p>Log Out</p>
                                </a>
                            </div>
                        </div>
                    </li>
                </ul>
        </div>
        <div class="content">
            <main>
                <div class="search-filter-bar">
                    <div class="search-container">
                        <div class="search-barA" id="search-barA">
                            <input type="text" id="search-input" placeholder="Search for a gown...">
                            <button id="search-button" onclick="search()">Search</button>
                        </div>
                    </div>
                </div>
                <div class="container">
                    <div class="left-column">
                        <div class="filter-formbox" id="filter-formbox">
                            <h4 class="category-filter">Themes</h4>
                            <form class="filter-form" method="GET" id="filterForm">
                                <?php
                                if (!empty($themes)) {
                                    foreach ($themes as $theme) {
                                        echo '<label class="filter-toggle" data-value="' . htmlspecialchars($theme) . '" data-type="theme">
                        <div class="filter-box" data-state="blank"></div> ' . htmlspecialchars($theme) . '
                      </label>';
                                    }
                                } else {
                                    echo "No themes found.";
                                }
                                ?>
                                <input type="hidden" name="selectedTags" id="selectedTags"> <!-- Hidden field to store selected tags -->
                            </form>
                            <br>
                            <hr>
                            <br>
                            <h4 class="category-filter">Colors</h4>
                            <form class="filter-form" method="GET" id="filterForm">
                                <?php
                                if (!empty($colors)) {
                                    foreach ($colors as $color) {
                                        echo '<label class="filter-toggle" data-value="' . htmlspecialchars($color) . '" data-type="color">
                        <div class="filter-box" data-state="blank"></div> ' . htmlspecialchars($color) . '
                      </label>';
                                    }
                                } else {
                                    echo "No colors found.";
                                }
                                ?>
                            </form>
                            <br>
                            <hr>
                            <br>
                            <h4 class="category-filter">Sizes</h4>
                            <form class="filter-form" method="GET" id="filterForm">
                                <?php

                                $desired_sizes = ['Extra small', 'Small', 'Medium', 'Large'];

                                foreach ($desired_sizes as $size) {
                                    echo '<label class="filter-toggle" data-value="' . htmlspecialchars($size) . '" data-type="size">
            <div class="filter-box" name="sizes[]" value=" data-state="blank"></div> ' . htmlspecialchars($size) . '
          </label>';
                                }
                                ?>
                                <input type="hidden" name="selectedTags" id="selectedTags"> <!-- Hidden field to store selected tags -->
                            </form>
                            <div class="form-buttons">
                                <button type="submit" class="apply-button" form="filterForm">Apply</button>
                                <button type="button" class="clear-button" onclick="clearFilters()">Clear</button>
                            </div>
                        </div>
                    </div>
                    <div class="right-column">
                        <div class="sort-container">
                            <p>Sort by</p>
                            <button id="top-sales-button" onclick="sortTopSales()">Top Sales</button>
                            <select id="rent-sort-options" onchange="sortProducts()">
                                <option value="" disabled selected>Rent Price</option>
                                <option value="rent-price-asc">Low to High</option>
                                <option value="rent-price-desc">High to Low</option>
                            </select>
                            <a href="Color Analysis.php"><button class="analysis">Color Analysis</button></a>
                            <button id="rentSelectedBtn" class="multi">Rent Selected</button>
                        </div>

                        <div class="product-display">
                            <?php while ($row = mysqli_fetch_assoc($select)) { ?>
                                <div class="card-wrapper">
                                    <div class="image" style="position: relative;">
                                        <?php if ($row['status'] != 1) { // Only show checkbox if gown is not rented 
                                        ?>
                                            <input type="checkbox"
                                                class="gown-checkbox"
                                                data-gown-id="<?php echo $row['id']; ?>"
                                                data-gown-name="<?php echo htmlspecialchars($row['name']); ?>"
                                                data-gown-price="<?php echo $row['price']; ?>"
                                                onclick="event.stopPropagation()"
                                                style="position: absolute; top: 10px; right: 10px; z-index: 10; width: 20px; height: 20px;">
                                        <?php } ?>
                                        <a href="Preview.php?id=<?php echo $row['id']; ?>" class="card-link">
                                            <div class="card">
                                                <?php
                                                $images = @unserialize($row['img']);
                                                if ($images === false && $row['img'] !== 'b:0;') {
                                                    $images = [$row['img']];
                                                }

                                                if (!empty($images)) {
                                                    $image = $images[0];
                                                    echo '<img src="uploaded_img/' . htmlspecialchars($image) . '" alt="" style="width: 200px; height: 250px;">';
                                                }
                                                ?>
                                                <div class="caption">
                                                    <p class="product_name ellipsis"><?php echo $row['name']; ?></p>
                                                    <?php if ($row['status'] == 1) { ?>
                                                        <p class="tally_status rented">Rented</p>
                                                    <?php } else if ($row['tally'] == 0) { ?>
                                                        <p class="tally_status">Brandnew</p>
                                                    <?php } else { ?>
                                                        <p class="tally_status">Used</p>
                                                    <?php } ?>
                                                    <p class="price"><b>Rent: ₱<?php echo number_format($row['price'], 2); ?></b></p>
                                                </div>
                                            </div>
                                        </a>
                                    </div>
                                </div>
                            <?php } ?>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const checkboxes = document.querySelectorAll('.gown-checkbox');
            const rentSelectedBtn = document.getElementById('rentSelectedBtn');

            checkboxes.forEach(checkbox => {
                checkbox.addEventListener('change', function() {
                    const checkedBoxes = document.querySelectorAll('.gown-checkbox:checked');
                    rentSelectedBtn.style.display = checkedBoxes.length > 0 ? 'block' : 'none';
                });
            });

            rentSelectedBtn.addEventListener('click', function() {
                const selectedGowns = [];
                document.querySelectorAll('.gown-checkbox:checked').forEach(checkbox => {
                    selectedGowns.push({
                        id: checkbox.dataset.gownId,
                        name: checkbox.dataset.gownName,
                        price: checkbox.dataset.gownPrice
                    });
                });

                if (selectedGowns.length > 0) {
                    const gownIds = selectedGowns.map(gown => gown.id).join(',');
                    window.location.href = `Preview.php?id=${gownIds}&multi=true`;
                }
            });
        });
        // Replace your existing history handling code with this:
        let currentIndex = 0;
        let histories = [window.location.href];

        window.onpopstate = function(event) {
            if (event.state) {
                // Navigate based on direction
                if (event.state.index < currentIndex) {
                    // Going back
                    window.location.href = event.state.url;
                } else {
                    // Going forward
                    window.location.href = event.state.url;
                }
                currentIndex = event.state.index;
            }
        };

        // Push initial state
        history.replaceState({
            index: currentIndex,
            url: window.location.href
        }, '', window.location.href);

        // Handle links with proper history tracking
        document.querySelectorAll('a').forEach(link => {
            link.addEventListener('click', function(e) {
                // Don't handle external links or # links
                if (this.hostname !== window.location.hostname || this.getAttribute('href') === '#') {
                    return;
                }

                e.preventDefault();
                currentIndex++;
                const newUrl = this.href;
                histories.push(newUrl);

                // Push new state
                history.pushState({
                    index: currentIndex,
                    url: newUrl
                }, '', newUrl);

                // Navigate to new page
                window.location.href = newUrl;
            });
        });
        window.onload = function() {
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.has('selectedTags')) {

                const url = new URL(window.location.href);
                url.searchParams.delete('selectedTags');
                history.replaceState(null, '', url);
                document.getElementById('selectedTags').value = '';
                selectedTags = [];
            }

            document.querySelectorAll('.filter-box').forEach(function(box) {
                box.setAttribute('data-state', 'blank');
                box.textContent = '';
                box.style.color = '';
            });
        };

        function sortTopSales() {
            const button = document.getElementById('top-sales-button');
            const params = new URLSearchParams(window.location.search);
            const isActive = button.classList.toggle('active');

            if (isActive) {
                params.set('sort', 'top-sales');
            } else {
                params.delete('sort');
            }

            window.location.href = '?' + params.toString();
        }

        document.addEventListener('DOMContentLoaded', () => {
            const params = new URLSearchParams(window.location.search);
            const sortOption = params.get('sort');

            if (sortOption === 'top-sales') {
                document.getElementById('top-sales-button').classList.add('active');
            }
        });

        function sortProducts() {
            const sortOption = document.getElementById('rent-sort-options').value;
            const params = new URLSearchParams(window.location.search);
            params.set('sort', sortOption);
            window.location.href = '?' + params.toString();
        }

        document.addEventListener('DOMContentLoaded', () => {
            const params = new URLSearchParams(window.location.search);
            const sortOption = params.get('sort');

            if (sortOption) {
                document.getElementById('rent-sort-options').value = sortOption;
            }
        });

        function toggleDropdown() {
            var dropdown = document.getElementById("myDropdown");
            dropdown.classList.toggle("show");
        }

        var product_id = document.getElementsByClassName("add");
        for (var i = 0; i < product_id.length; i++) {
            product_id[i].addEventListener("click", function(event) {
                var target = event.target;
                var id = target.getAttribute("data-id");
                var xml = new XMLHttpRequest();
                xml.onreadystatechange = function() {
                    if (this.readyState == 4 && this.status == 200) {
                        var data = JSON.parse(this.responseText);
                        target.innerHTML = data.in_cart;
                        document.getElementById("badge").innerHTML = data.num_cart + 1;
                    }
                };

                xml.open("GET", "connection.php?id=" + id, true);
                xml.send();
            });
        }

        document.querySelectorAll('.filter-box').forEach(function(box) {
            box.addEventListener('click', function() {
                let state = this.getAttribute('data-state');
                const tag = this.parentElement.getAttribute('data-value');
                const type = this.parentElement.getAttribute('data-type');

                if (state === 'blank') {
                    this.setAttribute('data-state', 'check');
                    this.textContent = '✔';
                    addTag(tag, 'check', type);
                } else if (state === 'check') {
                    this.setAttribute('data-state', 'x');
                    this.textContent = 'X';
                    addTag(tag, 'x', type);
                } else {
                    this.setAttribute('data-state', 'blank');
                    this.textContent = '';
                    removeTag(tag, type);
                }
            });
        });

        let selectedTags = [];

        function addTag(tag, state, type) {
            const existingTag = selectedTags.find(t => t.tag === tag && t.type === type);
            if (existingTag) {
                existingTag.state = state;
            } else {
                selectedTags.push({
                    tag: tag,
                    state: state,
                    type: type
                });
            }
            document.getElementById('selectedTags').value = JSON.stringify(selectedTags);
        }

        function removeTag(tag, type) {
            selectedTags = selectedTags.filter(t => t.tag !== tag || t.type !== type);
            document.getElementById('selectedTags').value = JSON.stringify(selectedTags);
        }

        function toggleSearch() {
            var searchBar = document.getElementById("search-barA");
            if (searchBar.style.display === "none" || searchBar.style.display === "") {
                searchBar.style.display = "block";
            } else {
                searchBar.style.display = "none";
            }
        }

        function search() {
            const searchInput = document.getElementById('search-input').value;
            const params = new URLSearchParams();
            params.append('search', searchInput);


            window.location.href = '?' + params.toString();
        }

        function clearFilters() {

            window.location.reload();
        }


        document.addEventListener('click', function(event) {
            const searchBar = document.getElementById('search-barA');
            const dropdown = document.getElementById('myDropdown');


            if (!dropdown.contains(event.target) && event.target.className !== 'dropbtn') {
                dropdown.classList.remove('show');
            }
        });
    </script>
</body>

</html>

<?php $conn->close(); ?>