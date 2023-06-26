<!DOCTYPE html>
<html>
<head>
    <title>Sprawdzanie grup bez administratorów</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css">
</head>
<body>
    <div class="container mt-5">
        <h2>Sprawdzanie grup bez administratorów</h2>
        <form id="adminCheckForm">
            <div class="form-group">
                <label for="accessToken">Token dostępu (jeden pod drugim):</label>
                <textarea class="form-control" id="accessToken" required></textarea>
            </div>
            <div class="form-group">
                <label for="groupList">Lista UID grup (jeden pod drugim):</label>
                <textarea class="form-control" id="groupList" required></textarea>
            </div>
            <button type="submit" class="btn btn-primary">Sprawdź grupy</button>
        </form>
        <div id="resultContainer" class="mt-5"></div>

        <script>
            document.getElementById("adminCheckForm").addEventListener("submit", function (event) {
                event.preventDefault();

                var accessTokens = document.getElementById("accessToken").value.trim().split("\n");
                var groupList = document.getElementById("groupList").value.trim().split("\n");

                var graphAPIEndpoint = "https://graph.facebook.com/graphql";
                var batchSize = 500;
                var currentBatch = 0;
                var csvData = [];

                checkGroups();

                function checkGroups() {
                    var currentGroup = groupList[currentBatch];
                    var currentAccessToken = accessTokens[Math.floor(currentBatch / batchSize) % accessTokens.length].trim();
                    var query = `
                        {
                            group(id: "${currentGroup}") {
                                name
                                admins {
                                    data {
                                        uid
                                        name
                                    }
                                }
                                member_count
                            }
                        }
                    `;

                    fetch(graphAPIEndpoint, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Authorization': `Bearer ${currentAccessToken}`
                        },
                        body: JSON.stringify({ query })
                    })
                    .then(function (response) {
                        return response.json();
                    })
                    .then(function (data) {
                        var group = data.data.group;
                        var groupName = group.name;
                        var admins = group.admins.data;
                        var memberCount = group.member_count;

                        if (admins.length === 0) {
                            var groupData = {
                                "Nazwa grupy": groupName,
                                "UID grupy": currentGroup,
                                "Ilość członków": memberCount,
                                "Pełny URL": "https://www.facebook.com/groups/" + currentGroup
                            };
                            csvData.push(groupData);
                        }

                        currentBatch++;

                        if (currentBatch % batchSize === 0) {
                            promptForTokenChange();
                        } else {
                            if (currentBatch === groupList.length) {
                                generateCSVFile();
                            } else {
                                updateResultContainer();
                                checkGroups();
                            }
                        }
                    })
                    .catch(function (error) {
                        console.log("Wystąpił błąd:", error);
                        currentBatch++;

                        if (currentBatch % batchSize === 0) {
                            promptForTokenChange();
                        } else {
                            if (currentBatch === groupList.length) {
                                generateCSVFile();
                            } else {
                                updateResultContainer();
                                checkGroups();
                            }
                        }
                    });
                }

                function promptForTokenChange() {
                    var resultContainer = document.getElementById("resultContainer");
                    resultContainer.appendChild(document.createElement("hr"));
                    resultContainer.appendChild(document.createElement("br"));
                    resultContainer.appendChild(document.createElement("br"));
                    resultContainer.appendChild(document.createElement("p")).textContent = "Przetworzono " +
                        currentBatch + " grup. Wprowadź nowy token dostępu:";
                    var newAccessTokenInput = document.createElement("textarea");
                    newAccessTokenInput.className = "form-control";
                    newAccessTokenInput.required = true;
                    newAccessTokenInput.rows = 5;
                    newAccessTokenInput.id = "newAccessToken";
                    resultContainer.appendChild(newAccessTokenInput);

                    var submitButton = document.createElement("button");
                    submitButton.className = "btn btn-primary";
                    submitButton.textContent = "Zatwierdź";
                    resultContainer.appendChild(document.createElement("br"));
                    resultContainer.appendChild(submitButton);

                    submitButton.addEventListener("click", function (event) {
                        event.preventDefault();
                        var newAccessToken = document.getElementById("newAccessToken").value.trim();
                        accessTokens.push(newAccessToken);
                        resultContainer.innerHTML = "";
                        checkGroups();
                    });
                }

                function updateResultContainer() {
                    var resultContainer = document.getElementById("resultContainer");
                    resultContainer.innerHTML = "Przetworzono " + currentBatch + " z " + groupList.length + " grup...";
                }

                function generateCSVFile() {
                    var csvContent = "data:text/csv;charset=utf-8,";
                    csvContent += Object.keys(csvData[0]).join(",") + "\n";

                    csvData.forEach(function (row) {
                        var rowData = Object.values(row).map(function (value) {
                            return '"' + value + '"';
                        });
                        csvContent += rowData.join(",") + "\n";
                    });

                    var encodedUri = encodeURI(csvContent);
                    var link = document.createElement("a");
                    link.setAttribute("href", encodedUri);
                    link.setAttribute("download", "grupy_bez_administratorow.csv");
                    link.textContent = "Pobierz wyniki";

                    var resultContainer = document.getElementById("resultContainer");
                    resultContainer.appendChild(document.createElement("hr"));
                    resultContainer.appendChild(document.createElement("br"));
                    resultContainer.appendChild(link);
                }
            });
        </script>
    </div>
</body>
</html>
