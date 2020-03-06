
const STAGING = "http://dummy.restapiexample.com/api/v1/employees";
const ENVIRONMENT = STAGING;
const SERVER_URL = "http://192.168.1.155/ChatDemoAPI/ChatApp.php?Service="

module.exports = {

    getLogin(params) {
        return fetch(`${SERVER_URL + 'Login'}`, {
            method: 'POST',
            headers: {
                "Content-Type": "application/json"
            },
            body: JSON.stringify(params)
        })
            .then(response => response.json()
                .then(data => {
                    return data;
                }))
    },
    registerUser(params) {
        return fetch(`${SERVER_URL + 'Register'}`, {
            method: 'POST',
            headers: {
                "Content-Type": "application/json"
            },
            body: JSON.stringify(params)
        })
            .then(response => response.json()
                .then(data => {
                    return data;
                }))
    },
    getAlbumList(params) {
        // return fetch(`${'https://jsonplaceholder.typicode.com/posts'}`, {
        //     method: 'GET',
        //     headers: {
        //         "Content-Type": "application/json"
        //     }
        // })
        //     .then(response => response.json()
        //         .then(data => {
        //             return data;
        //         }))
    }
}