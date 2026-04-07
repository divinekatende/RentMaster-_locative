// init.js : initialisation des locataires
if (!localStorage.getItem("locataires")) {

  let locataires = [
    {
      id: 1,
      nom: "Divine",
      email: "divine@email.com",
      matricule: "A112",
      password: "4464",
      photo: ""
    },
    {
      id: 2,
      nom: "Junior",
      email: "junior@email.com",
      matricule: "A123",
      password: "1111",
      photo: ""
    }
  ];

  localStorage.setItem("locataires", JSON.stringify(locataires));
}