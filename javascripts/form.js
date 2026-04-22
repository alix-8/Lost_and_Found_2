document.addEventListener("DOMContentLoaded", () => {
    const status = document.getElementById("status");
    const dateLbl = document.getElementById("dateLabel");
    const locLost = document.getElementById("location_lost");
    const locFound = document.getElementById("location_found");

    function updateForm() {
        // para sa date labels
        if (status.value === "lost") {
            dateLbl.textContent = "Date Lost";
        } else if (status.value === "found") {
            dateLbl.textContent = "Date Found";
        } else {
            dateLbl.textContent = "Date";
        }

        // para sa location fields
        if (status.value === "lost") {
            locLost.disabled = false;
            locFound.disabled = true;
            locFound.value = "";
        } else if (status.value === "found") {
            locLost.disabled = true;
            locLost.value = "";
            locFound.disabled = false;
        } else {
            locLost.disabled = false;
            locFound.disabled = false;
        }
    }

    // Run on dropdown change
    status.addEventListener("change", updateForm);

});
