document.getElementById("executeBtn").addEventListener("click", function() {
    const fileInput = document.getElementById("fileInput").files[0];
    const inputType = document.querySelector("input[name='inputType']:checked").value;
    const outputType = document.querySelector("input[name='outputType']:checked").value;
    const email = document.getElementById("email").value;
    
    if (!fileInput) {
        alert("Выберите файл.");
        return;
    }
    
    if (outputType === "email" && email.trim() === "") {
        alert("Введите адрес электронной почты.");
        return;
    }
    
    const formData = new FormData();
    formData.append("file", fileInput);
    formData.append("inputType", inputType);
    formData.append("outputType", outputType);
    formData.append("email", email);
    
    fetch("FileProceccer.php", {
        method: "POST",
        body: formData
    })
    .then(response => response.text())
    .then(data => {
        document.getElementById("output").innerHTML = data;
    })
    .catch(error => console.error("Error:", error));
});

document.querySelectorAll("input[name='outputType']").forEach(radio => {
    radio.addEventListener("change", function() {
        const emailField = document.getElementById("emailField");
        emailField.style.display = this.value === "email" ? "block" : "none";
    });
});
