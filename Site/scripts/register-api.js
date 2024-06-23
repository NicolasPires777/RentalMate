function callapi(event){
    event.preventDefault();
    console.log("Função chamada corretamente");

    const nome = document.querySelector('#nome').value;
    const senha = document.querySelector('#senha').value;
    const telefone = document.querySelector('#telefone').value;
    const email = document.querySelector("#email").value

    const data = {
        nome: nome,
        senha: senha,
        email: email,
        telefone: telefone
    };

    fetch('http://localhost/RentalMate/backend-api/register.php',{
        method: 'POST',
        headers: {
            'Content-Type' : 'application/json'
        },
        body: JSON.stringify(data)
    })
    .then(response => response.json())
    .then(result=>{
        if (result.Concluido){
            document.querySelector('.resposta').innerHTML = '<p style="color: green;">Registro concluído, <a href="./index.html">Clique aqui para logar</a></p>';
        } else if (result.erro){
            document.querySelector('.resposta').innerHTML = '<p style="color: red;">Esse email já está registrado</p>';
        } else {
            document.querySelector('.resposta').innerHTML = '<p style="color: red;">Erro não identificado, contate o suporte.</p>';
        }
    });
}

function callapilogin(event){
    event.preventDefault();
    console.log("Função chamada corretamente");

    const nome = document.querySelector('#email').value;
    const senha = document.querySelector('#senha').value

    const data = {
        nome: nome,
        senha: senha
    };

    fetch('http://localhost/RentalMate/backend-api/login.php',{
        method: 'POST',
        headers: {
            'Content-Type' : 'application/json'
        },
        body: JSON.stringify(data)
    })
    .then(response => response.json())
    .then(result=>{
        if (result.id){
            window.location.href = "./menu.php"
        } else if (result.errologin){
            document.querySelector('.resposta').innerHTML = '<p style="color : red;">Senha incorreta</p>';
        } else {
            document.querySelector('.resposta').innerHTML = '<p style="color : red">Este email não está registrado</p>';
        }
    });
}
