function showClientSteps() {
    document.getElementById("client-steps").classList.add("active");
    document.getElementById("contractor-steps").classList.remove("active");
}

function showContractorSteps() {
    document.getElementById("contractor-steps").classList.add("active");
    document.getElementById("client-steps").classList.remove("active");
}